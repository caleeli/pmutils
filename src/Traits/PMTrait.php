<?php

namespace David\PmUtils\Traits;

use Dotenv\Dotenv;
use PDO;
use PDOException;

trait PMTrait
{
    public function screen_remove_inspector($path)
    {
        $json = json_decode(file_get_contents($path));
        //$this->__screen_remove_inspector($json);
        echo json_encode($this->__screen_remove_inspector($json), \JSON_PRETTY_PRINT), "\n";
    }
    private function __screen_remove_inspector($json)
    {
        foreach ($json as $key => $value) {
            if (is_object($json) && $key === 'inspector') {
                unset($json->$key);
            } elseif (is_array($value) || is_object($value)) {
                $this->__screen_remove_inspector($value);
            }
        }
        return $json;
    }

    public function migrate($path = '')
    {
        $cwd = getcwd();
        if ($path) {
            chdir($path);
        }
        $dotenv = Dotenv::createImmutable($path);
        $dotenv->load();
        $dns1 = "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOSTNAME']};port={$_ENV['DB_PORT']};";
        $dns2 = "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOSTNAME']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']};";
        try {
            $pdo1 = new PDO($dns1, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
            $pdo2 = new PDO($dns2, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        } catch (PDOException $ex) {
            if ($ex->getCode() == 1049) {
                $pdo1->exec("create database {$_ENV['DB_DATABASE']};");
            }
        }
        $this->exec('php artisan migrate:fresh --seed');
    }
}
