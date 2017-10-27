<?php
include_once('Color.php');

class WS281X {
  public $Frequency = 800000;
  public $GpioPin = 18;
  public $Dma = 3;
  public $Intensity = 1;

  private $initialized = FALSE;

  private $leds = [];

  public function __construct(int $gpioPin = 18, int $lightCount = 150, float $intensity = 1) {
    $this->GpioPin = $gpioPin;
    $this->Intensity = $intensity;

    $this->leds = array_fill(0, $lightCount, COLOR::CLEAR());
  }

  private function GetFullRange(): array {
    return range(0, count($this->leds) - 1);
  }

  public function Render() :self {
    rpi_ws281x_render($this->GpioPin, count($this->leds), array_map(function($led) { return $led->Intensify($this->Intensity)->ToColorInt(); }, $this->leds));

    return $this;
  }

  public function Set(Color $color, $range = NULL, bool $render = FALSE): self {
    if (isset($range) && is_numeric($range)) {
      $range = [$range]; //if input is numeric, set range to single element array
    } elseif(!isset($range) || !is_array($range) || count($range) == 0) {
      $range = $this->GetFullRange(); //if range isn't set, set to entire strip
    }

    //Loop through range to setup Led array
    foreach($range as $ledNum) {
      if ($ledNum >= 0 && $ledNum < count($this->leds) && !$color->IsNone()) {
        $this->leds[$ledNum] = $color;
      }
    }

    //Render immediately if requested
    if ($render) {
      $this->Render();
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
      $this->Set($color, $range, TRUE);
      $this->MSleep($delay);
      $this->Clear($range, TRUE);
      $this->MSleep($delay / 2);
    }

    if ($restoreOriginal) {
      $this->leds = $temp;
      $this->Render();
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
      $this->Render();

      $color = array_pop($colors);
      array_unshift($colors, $color);
      $colors = array_values($colors);
      $this->MSleep($delay);
    }
    
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
      $this->Set($color, $ledNum, TRUE);
      $this->MSleep($delay);
    }
    
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
        $previousColors[$ledNum] = $this->leds[$ledNum];
        $this->Set($colorsInStep[$j], $ledNum, FALSE);
      }
      
      $this->Render()->MSleep($delay);
      
      foreach($previousColors as $ledNum=>$color) {
        $this->Set($color, $ledNum, FALSE);
      }
    }
    
    $this->Render(); //Render last set of previous colors back.
    
    return $this;
  }
}
