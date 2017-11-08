<?php
include_once('Color.php');

class WS281X {
  public $Frequency = 800000;
  public $GpioPin = 18;
  public $Dma = 3;
  public $Intensity = 1;

  private $initialized = FALSE;

  private $leds = [];
  private $filename;

  public function __construct(int $gpioPin = 18, int $lightCount = 150, float $intensity = 1) {
    $this->GpioPin = $gpioPin;
    $this->Intensity = $intensity;

    $this->leds = [];
    $this->filename = "/var/WS281X/$gpioPin-$lightCount.save";

    if (file_exists($this->filename)) {
      $leds = json_decode(file_get_contents($this->filename));
    }

    if(isset($leds)) {
      foreach($leds as $objectData) {
        $this->leds[] = Led::Hydrate($objectData);
      }
    } else {   
      for($i = 0; $i < $lightCount; ++$i) {
        $this->leds[] = new Led($i);
      }
      $this->Save();
    }
  }

  public function Save() {
    file_put_contents($this->filename, json_encode($this->leds));
  }

  public function GetCount(): int {
    return count($this->leds);
  }

  public function GetLeds(): array {
    return $this->leds;
  }

  private function GetFullRange(): array {
    return range(0, count($this->leds) - 1);
  }

  public function Render(bool $save = TRUE) :self {
    rpi_ws281x_render($this->GpioPin, 
      count($this->leds), 
      array_map(function($led) { return $led->GetColor()->Intensify($this->Intensity)->ToColorInt(); }, $this->leds));
    if ($save) {
      $this->Save();
    }
    return $this;
  }

  public function Set(Color $color, $range = NULL, bool $render = FALSE, bool $save = TRUE): self {
    if (isset($range) && is_numeric($range)) {
      $range = [$range]; //if input is numeric, set range to single element array
    } elseif(!isset($range) || !is_array($range) || count($range) == 0) {
      $range = $this->GetFullRange(); //if range isn't set, set to entire strip
    }

    //Loop through range to setup Led array
    foreach($range as $ledNum) {
      if ($ledNum >= 0 && $ledNum < count($this->leds) && !$color->IsNone()) {
        $this->leds[$ledNum]->SetColor($color);
      }
    }

    //Render immediately if requested
    if ($render) {
      $this->Render($save);
    }
    
    return $this;
  }
  
  public function MSleep(int $milliseconds) :self {
    usleep($milliseconds * 1000);
    
    return $this;
  }
  
  public function Sleep(int $seconds) :self {
    sleep($seconds);
    
    return $this;
  }
  
  private function ResolveColorArray($colorInput): array {
    if (is_array($colorInput)) {
      foreach($colorInput as &$color) {
        if (is_null($color)) {
          $color = Color::CLEAR();
        } elseif (is_numeric($color)) {
          $color = Color::FromColorInt($color);
        }
      }
      return $colorInput;
    } elseif ($colorInput instanceof Color) {
      return [$colorInput, Color::CLEAR(), Color::CLEAR()];
    } elseif (is_numeric($colorInput)) {
      return [Color::FromColorInt($colorInput), Color::CLEAR(), Color::CLEAR()];
    } else {
      return [Color::WHITE(), Color::CLEAR(), Color::CLEAR()];
    }    
  }

  public function Clear($range = NULL, bool $render = TRUE) :self {
    $this->Set(Color::CLEAR(), $range, $render);
    
    return $this;
  }

  public function Flash(Color $color = NULL, $range = NULL, int $delay = 250, int $times = 4, bool $restoreOriginal = TRUE) :self {
    $temp = $this->leds;

    for($i = 0; $i < $times; ++$i) {
      $this->Clear($range, TRUE);
      $this->MSleep($delay / 2);
      if (!isset($color)) {
        $color = Color::RED();
      }
      $this->Set($color, $range, TRUE, FALSE);
      $this->MSleep($delay);
      $this->Clear($range, TRUE);
      $this->MSleep($delay / 2);
    }

    if ($restoreOriginal) {
      $this->leds = $temp;
      $this->Render(FALSE);
    }
    
    return $this;
  }

  public function TheaterChase($colorInput, array $range = NULL, int $delay = 100, int $steps = 30) :self {
    $colors = $this->ResolveColorArray($colorInput);

    $colorCount = count($colors);

    if(!isset($range) || count($range) == 0) {
      $range = $this->GetFullRange();
    }

    for($loop = 0; $loop < $steps; ++$loop) {
      for($i = 0; $i < count($range); ++$i) {
        $this->Set($colors[$i % $colorCount], $range[$i], FALSE);
      }
      $this->Render(FALSE);

      $color = array_pop($colors);
      array_unshift($colors, $color);
      $colors = array_values($colors);
      $this->MSleep($delay);
    }
    $this->Save();
    return $this;
  }


  public function ColorWipe(Color $color, array $range = NULL, int $delay = 250) :self {
    return Cascade($color, $range, $delay);
  }

  public function Cascade(Color $color, array $range = NULL, int $delay = 250) :self {
    if(!isset($range) || count($range) == 0) {
      $range = $this->GetFullRange();
    }
    foreach($range as $ledNum) {
      $this->Set($color, $ledNum, TRUE, FALSE);
      $this->MSleep($delay);
    }
    $this->Save(); 
    return $this;
  }
  
  public function Run(Color $color, array $range = NULL, int $delay = 250) :self {
    return $this->Push([$color], $range, $delay);
  }
  
  public function Push(array $colors, array $range = NULL, int $delay = 250) :self {
    $previousColors = [];
    
    if(!isset($range) || count($range) == 0) {
      $range = $this->GetFullRange();
    }
    
    $steps  = count($range) + count($colors) - 1;
    
    for($i = 0; $i < $steps; ++$i) {
      $previousColors = [];
      $startColor = $i < count($range) ? 0 : $i - count($range) + 1;
      $endColor = $i < count($colors) ? $i : count($colors) - 1;
      $colorsInStep = array_values(array_slice($colors, $startColor, $endColor - $startColor + 1));
      
      $startLedIndex = $i < count($colors) ? 0 : $i - count($colors) + 1;
      $endLedIndex = $i < count($range) ? $i : count($range) - 1;
      for($j = 0; $j < count($colorsInStep); $j++) {
        $ledNum = $range[$endLedIndex - $j];
        $previousColors[$ledNum] = $this->leds[$ledNum]->GetColor();
        $this->Set($colorsInStep[$j], $ledNum, FALSE);
      }
      
      $this->Render(FALSE)->MSleep($delay);
      
      foreach($previousColors as $ledNum=>$color) {
        $this->Set($color, $ledNum, FALSE);
      }
    }
    
    $this->Render(); //Render last set of previous colors back.
    
    return $this;
  }
}
