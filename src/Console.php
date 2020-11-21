<?php

namespace David\PmUtils;

use David\PmUtils\Traits\DBTrait;
use ReflectionMethod;
use ReflectionObject;

class Console
{
    use MonitorTrait;
    use DBTrait;

    public $composer;

    public function __construct($filename)
    {
        $this->composer = $this->load($filename);
    }

    /**
     * Muestra la lista de comandos
     *
     * @return void
     */
    public function help()
    {
        $ref = new ReflectionObject($this);
        foreach($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === '__construct') continue;
            $docs = explode("\n", $method->getDocComment());
            $params = [];
            foreach($method->getParameters() as $p) {
                $params[] = '$'. $p->getName();
            }
            $this->print(
                sprintf(
                    "\033[1m%-12s\033[0m \033[3m%-13s\033[0m %s",
                    $method->getName(),
                    implode(',', $params),
                    substr(trim($docs[1] ?? ''), 2)
                )
            );
        }
    }

    private function load($filename)
    {
        return @json_decode(file_get_contents($filename));
    }

    public function title($title)
    {
        echo str_repeat('*', 40), "\n";
        echo $title, "\n";
        echo str_repeat('*', 40), "\n";
    }

    public function print($text)
    {
        echo $text, "\n";
    }

    private function save()
    {
        $json = str_replace(
            ['\/', '    '],
            ['/', '  '],
            json_encode($this->composer, JSON_PRETTY_PRINT)
        ) . "\n";
        file_put_contents('composer.json', $json);
    }

    /**
     * Instala un paquete a pm4
     *
     * @param string $package
     */
    public function install($package)
    {
        $this->print("Install package: $package");
        $this->add_repo($package);
        $pack = $this->pack_name($package);
        passthru("composer require $pack");
        $install = explode('/', $pack)[1] . ':install';
        passthru("php artisan $install");
    }

    public function add_repo($name)
    {
        if (!$this->find_repo($name)) {
            array_unshift(
                $this->composer->repositories,
                (object) [
                    'type' => 'path',
                    'url' => "../{$name}",
                ]
            );
            $this->save();
        }
    }

    public function pack_name($name)
    {
        $composer = $this->load("../$name/composer.json");
        return $composer->name;
    }

    public function find_repo($name)
    {
        foreach($this->composer->repositories as $repo) {
            if (substr($repo->url, -strlen($name)) === $name) {
                return $repo;
            }
        }
        return null;
    }

    /**
     * Actualiza una libreria npm a una ruta destino
     *
     * @param [type] $path
     * @return void
     */
    public function npmupdate($path)
    {
        $config = $this->load('package.json');
        $path = "{$path}/node_modules/{$config->name}";
        $skip = ['node_modules', 'tests'];
        foreach(glob('*') as $file) {
            if (in_array(basename($file), $skip)) continue;
            if (is_file($file)) {
                $cmd = "cp $file $path/$file";
            } else {
                $cmd = "rsync -rp $file $path/$file";
            }
            $this->print($cmd);
            passthru($cmd . ' 2>&1');
        }
    }
}
