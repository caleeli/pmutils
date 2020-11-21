<?php

namespace David\PmUtils\Api;

class Service
{
    public function __construct($path)
    {
        $func = \array_shift($path);
        $res = $this->$func(...$path);
        if ($res) {
            echo \json_encode($res);
        }
    }

    public function restart($service)
    {
        $path = __DIR__ . '/../../queue/' . \time() . \rand() . '.json';
        $json = \json_encode([
            'command' => "service {$service} restart",
        ]);
        \file_put_contents($path, $json);
        return [
            'success' => true,
        ];
    }
}
