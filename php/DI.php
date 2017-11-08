<?php

class DI {
  private static $strand;

  public static function GetStrand(): WS281X {
    if (self::$strand == NULL) {
      self::$strand = new WS281X(18, 150);
    }
    return self::$strand;
  }
}
