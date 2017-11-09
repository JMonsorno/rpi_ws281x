<?php

include_once('WS281X.php');
include_once('Color.php');
include_once('Led.php');
include_once('HueMimic.php');

$mimic = new HueMimic();

$lightStrip = new WS281X(18, 150, .1);

$lightStrip
  ->Set(Color::BLUE(), [], TRUE)
  ->Set(Color::GREEN(), range(10, 149), TRUE)
  ->Sleep(3)
  ->Push([Color::WHITE(), Color::NONE(), Color::WHITE(), Color::NONE(), Color::WHITE()], [], 100)
  ->Sleep(1)
  ->Flash(Color::RED(), range(0, 9), 200, 2)
  ->Sleep(1)
  ->Cascade(Color::BLUE(), range(149, 10), 10)
  ->Cascade(Color::GREEN(), range(9, 0), 10)
  ->Sleep(1)
  ->Cascade(Color::CLEAR(), [], 10)
  ->TheaterChase([Color::RED(), Color::OFF(), Color::OFF(), Color::OFF(), Color::OFF()], [], 100, 40)
  ->Sleep(0);
// for($loop = 149; $loop > 0; --$loop) {
  // $lightStrip->SetLed($loop, 0, 0, 0,  TRUE);
// }

// for($loop = 0; $loop < 3000; ++$loop) {
  // $led = $loop % 300 >= 150 ? 149 - $loop % 150 : $loop % 150;
  
  // $lightStrip->Clear();
  // $lightStrip->SetLed($led, BLUE);
  // $lightStrip->Render();
// }

// $colors = [RED, WHITE, BLUE];
// for($loop = 0; $loop < 100; ++$loop) {
  
  // for($led = 0; $led < 150; ++$led) {
    // $lightStrip->SetLed($led, $colors[$led % 3]);
  // }
  // $lightStrip->Render();
  // $color = array_pop($colors);
  // array_unshift($colors, $color);
  // $colors = array_values($colors);
  // usleep(250000);
// }

//$lightStrip->Clear();
//var_dump(rpi_ws281x_test3(8, (55<<16) + (55 << 8) + (55 << 0)));
