<?php
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';

$path = explode('/', $_SERVER['PATH_INFO']);
array_shift($path);
$base = array_shift($path);
$class = 'David\PmUtils\Api\\' . preg_replace('/\W+/', '', ucwords($base, "-_"));

$_SERVER['REQUEST_METHOD'];

new $class($path);
