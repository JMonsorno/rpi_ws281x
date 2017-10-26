--TEST--
rpi_ws281x_test2() Basic test
--SKIPIF--
<?php
if (!extension_loaded('rpi_ws281x')) {
	echo 'skip';
}
?>
--FILE--
<?php 
var_dump(rpi_ws281x_test2());
var_dump(rpi_ws281x_test2('PHP'));
?>
--EXPECT--
string(11) "Hello World"
string(9) "Hello PHP"
