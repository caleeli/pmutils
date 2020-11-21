<?php

namespace David\PmUtils\Traits;

use David\PmUtils\Str;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;
use Exception;

trait DBTrait
{
    use TPLTrait;

    private function db_connect($url)
    {
        $connectionParams = array(
            'url' => $url,
        );
        $conn = DriverManager::getConnection($connectionParams);
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        return $conn;
    }

    /**
     * Exporta el esquema de una base de datos como migrations
     *
     * @return void
     */
    public function db_migration($url)
    {
        $conn = $this->db_connect($url);
        $sm = $conn->getSchemaManager();
        $tables = $sm->listTables();
        //$this->rmtree('database/migrations');
        foreach ($tables as $table) {
            $attributes = [];
            foreach ($table->getColumns() as $column) {
                $attributes[] = [
                    'name' => $column->getName(),
                    'type' => \lcfirst(substr($column->getType(), 1)),
                    'length' => $column->getLength(),
                    'not_null' => $column->getNotnull(),
                    'default' => $column->getDefault(),
                    'autoincrement' => $column->getAutoincrement(),
                ];
            }
            $this->execTemplate(
                $this->createTemplate('migration'),
                [
                    'name' => $table->getName(),
                    'attributes' => $attributes,
                ]
            );
        }
    }

    public function rmtree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->rmtree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function db_dump($url, $name)
    {
        $conn = $this->db_connect($url);
        $sm = $conn->getSchemaManager();
        $this->execTemplate(
            $this->createTemplate('seeder'),
            [
                'name' => $name,
            ]
        );
        $tables = $sm->listTables();
        $f = \fopen("database/seeders/{$name}.txt", 'w');
        foreach($tables as $table) {
            $name = $table->getName();
            $sql = "select * from {$name}";
            \fwrite($f, \json_encode($name) . "\n");
            $result = $conn->executeQuery($sql);
            while($row = $result->fetch(FetchMode::COLUMN)) {
                foreach($row as $c => $col) {
                    $row[$c] = $row[$c] === '0000-00-00' ? null : $row[$c];
                }
                $encoded = \json_encode($row);
                if (!$encoded) {
                    throw new Exception('Invalid data encoding: ' . \var_export($row, \true));
                }
                \fwrite($f, $encoded . "\n");
            }
        }
        \fclose($f);
    }
}
