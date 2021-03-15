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

    /**
     * Queue a command
     *
     */
    private function queueCommand($command)
    {
        $path = __DIR__ . '/../../queue/' . \time() . \rand() . '.json';
        $json = \json_encode([
            'command' => $command,
        ]);
        \file_put_contents($path, $json);
    }

    public function restart($service)
    {
        $this->queueCommand("service {$service} restart");
        return [
            'success' => true,
        ];
    }

    public function migrate()
    {
        $this->queueCommand("./pmtools migrate /home/david/workspace/processmaker");
        return [
            'success' => true,
        ];
    }

    public function clear_requests()
    {
        chdir($_ENV['PROCESSMAKER_HOME']);
        \passthru('echo "yes" | php artisan processmaker:clear-requests');
    }
}
