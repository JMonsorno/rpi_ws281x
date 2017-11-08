<?php
//router.php
spl_autoload_register(function ($class_name) {
  include $class_name . '.php';
});


if (preg_match('~/api/(.*)~', $_SERVER['REQUEST_URI'])) {
  (new ApiRequest($_SERVER['REQUEST_URI']))->Process();
} else {
  return false;
}
