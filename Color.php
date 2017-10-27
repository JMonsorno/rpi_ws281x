<?php

class Color {
  private $red, $green, $blue;
  private $flags = 0;
  
  private const NONE = 1 << 1;
  
  private function __construct(int $flags = 0) 
  {
    $this->flags = $flags;
  }
  
  public static function RED()    { return self::FromRGB(255, 0, 0); }
  public static function GREEN()  { return self::FromRGB(0, 255, 0); }
  public static function BLUE()   { return self::FromRGB(0, 0, 255); }
  public static function WHITE()  { return self::FromRGB(255, 255, 255); }
  public static function CLEAR()  { return self::FromRGB(0, 0, 0); }
  public static function OFF()    { return self::CLEAR(); }
  public static function NONE()   { return new Color(self::NONE); }
  
  public static function FromColorInt(int $colorInt) :self {
    $color = new Color();
    $color->red = $colorInt % 256;
    $color->green = ($colorInt >> 8) % 256;
    $color->blue = $colorInt >> 16;
    return $color;
  }
  
  public static function FromRGB(int $red, int $green, int $blue) :self {
    $color = new Color();
    $color->red = $red;
    $color->green = $green;
    $color->blue = $blue;
    return $color;
  }
  
  public function ToColorInt() :int {
    return ($this->blue << 16) + ($this->green << 8) + ($this->red << 0);
  }
  
  public function Intensify(float $factor) :self{
    $red = max(0, min(255, (int) ($this->red * $factor)));
    $green = max(0, min(255, (int) ($this->green * $factor)));
    $blue = max(0, min(255, (int) ($this->blue * $factor)));
    return self::FromRGB($red, $green, $blue);
  }
  
  public function IsNone(): bool {
    return ($this->flags & self::NONE) == self::NONE;
  }
}