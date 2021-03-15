<?php
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    foreach (parse_ini_file(__DIR__ . '/.env') as $key=>$value) {
        $_ENV[$key] = $value;
    }
}

$path = explode('/', $_SERVER['PATH_INFO']);
array_shift($path);
$base = array_shift($path);
$class = 'David\PmUtils\Api\\' . preg_replace('/\W+/', '', ucwords($base, "-_"));

$_SERVER['REQUEST_METHOD'];

new $class($path);
