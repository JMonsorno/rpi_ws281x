--TEST--
rpi_ws281x_test1() Basic test
--SKIPIF--
<?php
if (!extension_loaded('rpi_ws281x')) {
	echo 'skip';
}
?>
--FILE--
<?php 
$ret = rpi_ws281x_test1();

var_dump($ret);
?>
--EXPECT--
The extension rpi_ws281x is loaded and working!
NULL
