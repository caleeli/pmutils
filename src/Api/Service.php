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
        if ($service == 'pm4_horizon') {
            $pmHome = $_ENV['PROCESSMAKER_HOME'];
            $this->queueCommand("cd {$pmHome};php artisan horizon:terminate;service {$service} stop;php artisan optimize:clear;service {$service} start;");
        } else {
            $this->queueCommand("service {$service} restart");
        }
        return [
            'success' => true,
        ];
    }

    public function migrate($version)
    {
        $pmHome = $_ENV['PROCESSMAKER_HOME'];
        if ($version === '4.1') {
            $pmHome = '/home/david/projects/processmaker';
        }
        $this->queueCommand("./pmtools migrate {$pmHome}");
        return [
            'success' => true,
        ];
    }

    public function install()
    {
        $package = $_GET['package'];
        $version = $_GET['version'];
        $pmHome = $_ENV['PROCESSMAKER_HOME'];
        $pmtools = __DIR__ . '/../../bin/pmtools';
        $cwd = getcwd();
        chdir($pmHome);
        exec($pmtools . " install {$package} {$version} 2>&1", $output, $code);
        chdir($cwd);
        // json header
        header('Content-Type: application/json');
        return [
            'success' => $code === 0,
            'output' => implode("\n", $output),
        ];
    }

    public function clear_requests()
    {
        chdir($_ENV['PROCESSMAKER_HOME']);
        \passthru('echo "yes" | php artisan processmaker:clear-requests');
    }
}
