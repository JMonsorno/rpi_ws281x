--TEST--
Check if rpi_ws281x is loaded
--SKIPIF--
<?php
if (!extension_loaded('rpi_ws281x')) {
	echo 'skip';
}
?>
--FILE--
<?php 
echo 'The extension "rpi_ws281x" is available';
?>
--EXPECT--
The extension "rpi_ws281x" is available
