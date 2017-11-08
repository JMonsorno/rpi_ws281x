<?php

class Led implements JsonSerializable {
         
  //public $state, $swupdate, $type, $name, $modelid, $manufacturername, $uniquieid, $swversion;

  private $objectData;
  private $objectKeys;
  private $readOnly;
  private $writable;

  public function __construct(string $name) {
    $this->objectData = (object)[
      'state' => (object)['on'=>FALSE, 'xy'=>[0,0], 'red'=>0, 'green'=>0, 'blue'=>0, 'colormode'=>'xy', 'reachable'=>true],
      'swupdate' => (object)['state'=>'noupdates', 'lastinstall'=>NULL],
      'type' => 'WS281X',
      'name' => $name,
      'modelid' => 'RPI_WS281X',
      'manufacturername' => 'JMonsorno',
      'uniquieid' => uniqid(),
      'swversion' => "5.23.1.13452"
    ];
    $this->readOnly = ['swupdate', 'type', 'modelid', 'manufacturername', 'uniqueid', 'swversion'];
    $this->objectKeys = array_keys(get_object_vars($this->objectData));
    $this->writable = array_diff($this->objectKeys, $this->readOnly);
  }

  public static function Hydrate($objectData) :Led {
    $led = new Led('');
    $led->objectData = $objectData;
    return $led;
  }

  public function jsonSerialize() {
    return $this->objectData;
  }

  public function GetWritable() :array {
    return $this->writable;
  }

  public function __get($propName) {
    if (in_array($propName, $this->objectKeys)) {
      return $this->objectData->$propName;
    } else {
      return NULL;
    }
  }

  public function __set($propName, $value) {
    if (in_array($propName, $this->writable)) {
      $this->objectData->$propName = $value;
    }
  }

  public function SetColor(Color $color) {
    list($this->objectData->state->red, $this->objectData->state->green, $this->objectData->state->blue) = $color->ToRGB();

    //Convert RGB to X,Y
    //Scale from 0 to 1
    $red = $this->objectData->state->red / 255.0;
    $green = $this->objectData->state->green / 255.0;
    $blue = $this->objectData->state->blue / 255.0;

    //Gamma correction
    $red = self::GammaCorrectToLight($red);
    $green = self::GammaCorrectToLight($green);
    $blue = self::GammaCorrectToLight($blue);

    $X = $red * 0.664511 + $green * 0.154324 + $blue * 0.162028;
    $Y = $red * 0.283881 + $green * 0.668433 + $blue * 0.047685;
    $Z = $red * 0.000088 + $green * 0.072310 + $blue * 0.986039;

    if (($X + $Y + $Z) != 0) {
      $x = $X / ($X + $Y + $Z);
      $y = $Y / ($X + $Y + $Z);
    } else {
      $x = 0;
      $y = 0;
    }

    $this->objectData->state->xy = [$x, $y];
  }

  private static function GammaCorrectToLight(float $color) :float {
    return ($color > 0.04045) ? pow(($color + 0.055) / (1.0 + 0.055), 2.4) : ($color / 12.92);
  }

  public function GetColor() :Color {
    if ($this->objectData->state->on) {
      return Color::FromRGB($this->objectData->state->red, $this->objectData->state->green, $this->objectData->state->blue);
    } else {
      return Color::CLEAR();
    }
  }
}
