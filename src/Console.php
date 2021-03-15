<?php

namespace David\PmUtils;

use David\PmUtils\Traits\DBTrait;
use David\PmUtils\Traits\PMTrait;
use ReflectionMethod;
use ReflectionObject;

class Console
{
    use MonitorTrait;
    use DBTrait;
    use PMTrait;

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
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }
            $docs = explode("\n", $method->getDocComment());
            $params = [];
            foreach ($method->getParameters() as $p) {
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

    private function save($filename = 'composer.json', $data = null)
    {
        if ($data === null) {
            $data = $this->composer;
        }
        $json = str_replace(
            ['\/', '    '],
            ['/', '  '],
            json_encode($data, JSON_PRETTY_PRINT)
        ) . "\n";
        file_put_contents($filename, $json);
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
        if (!isset($this->composer->repositories)) {
            $this->composer->repositories = [];
        }
        foreach ($this->composer->repositories as $repo) {
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
        $this->npmbuild();
        $config = $this->load('package.json');
        $path = "{$path}/node_modules/{$config->name}";
        $skip = ['node_modules', 'tests', 'coverage', 'package.json'];
        foreach (glob('*') as $file) {
            if (in_array(basename($file), $skip)) {
                continue;
            }
            if (is_file($file)) {
                $this->exec("rm $path/$file");
                $this->exec("cp $file $path");
            } else {
                $this->exec("rm -Rf $path/$file");
                $this->exec("rsync -rp $file $path");
            }
        }
    }

    private function exec($cmd)
    {
        $this->print($cmd);
        passthru($cmd . ' 2>&1', $return);
        return $return;
    }

    public function commit($msg)
    {
        $dirs = [
            '../processmaker',
            '../modeler',
            '../screen-builder',
            '../vue-form-elements',
        ];
        $cwd = \getcwd();
        foreach ($dirs as $dir) {
            \chdir($dir);
            $this->exec('git commit --message=' . $msg);
        }
        \chdir($cwd);
    }

    public function push()
    {
        $dirs = [
            '../processmaker',
            '../modeler',
            '../screen-builder',
            '../vue-form-elements',
        ];
        $cwd = \getcwd();
        foreach ($dirs as $dir) {
            \chdir($dir);
            $this->exec('git push');
        }
        \chdir($cwd);
    }

    public function build()
    {
        $cwd = \getcwd();
        chdir('../vue-form-elements');
        $this->npmupdate('../screen-builder');
        $this->npmupdate('../modeler');
        $this->npmupdate('../processmaker');
        chdir('../screen-builder');
        $this->npmupdate('../modeler');
        $this->npmupdate('../processmaker');
        chdir('../modeler');
        $this->npmupdate('../processmaker');
        \chdir($cwd);
    }

    public function npm_compare_all()
    {
        $dirs = [
            '../processmaker',
            '../modeler',
            '../screen-builder',
            '../vue-form-elements',
        ];
        $cwd = \getcwd();
        $versions = [];
        foreach ($dirs as $dir) {
            $json = $this->load("{$dir}/package.json");
            foreach ($json->dependencies as $pack => $version) {
                $versions[$dir][$pack] = $version;
            }
            foreach ($json->devDependencies as $pack => $version) {
                $versions[$dir][$pack] = $version;
            }
        }
        // Remove non common
        $this->print(\sprintf("%25s %20s %20s %20s %20s", '/', ...$dirs));
        $lines = [];
        foreach ($dirs as $dir) {
            foreach ($versions[$dir] as $pack => $version) {
                if (isset($lines[$pack])) {
                    continue;
                }
                $vers = [];
                foreach ($dirs as $dir) {
                    if (isset($versions[$dir][$pack])) {
                        $vers[] = $versions[$dir][$pack];
                    }
                }
                if (count($vers) === count($dirs)) {
                    $lines[$pack] = $vers;
                }
            }
        }
        foreach ($lines as $pack => $vers) {
            $ok = true;
            foreach ($vers as $i => $v) {
                $ok = $ok && ($i === 0 || $v === $vers[$i-1]);
            }
            if (!$ok) {
                echo "\033[1m";
            }
            $this->print(\sprintf("%25s %20s %20s %20s %20s", $pack, ...$vers));
            echo "\033[0m";
        }
    }

    public function npm_compare($pack, $change = null)
    {
        $dirs = [
            '../processmaker',
            '../modeler',
            '../screen-builder',
            '../vue-form-elements',
        ];
        $buildScripts = [
            'build-bundle',
            'build',
            'dev',
        ];
        $cwd = \getcwd();
        foreach ($dirs as $dir) {
            $backup = $this->load("{$dir}/package.json");
            $json = $this->load("{$dir}/package.json");
            $version = $json->dependencies->$pack ?? $json->devDependencies->$pack ?? null;
            if (!$version) {
                continue;
            }
            $this->print(\sprintf("%25s: %s@%s", $dir, $pack, $version));
            if ($change && $version !== $change) {
                $this->print('******************************************************');
                if (isset($json->dependencies->$pack)) {
                    $json->dependencies->$pack = $change;
                }
                if (isset($json->devDependencies->$pack)) {
                    $json->devDependencies->$pack = $change;
                }
                $this->save("{$dir}/package.json", $json);
                \chdir($dir);
                if ($this->exec('npm install')) {
                    \chdir($cwd);
                    $this->print("ACTUALIZACION FALLO: INSTALL {$dir}");
                    $this->save("{$dir}/package.json", $backup);
                    break;
                }
                foreach ($buildScripts as $script) {
                    if (isset($json->scripts->$script)) {
                        if ($this->exec("npm run $script")) {
                            \chdir($cwd);
                            $this->print("ACTUALIZACION FALLO: BUILD {$dir}");
                            $this->save("{$dir}/package.json", $backup);
                            break 2;
                        }
                        break;
                    }
                }
                \chdir($cwd);
                $this->print('******************************************************');
                $this->print('');
            }
        }
    }

    private function npmbuild()
    {
        $buildScripts = [
            'build-bundle',
            'build',
            'dev',
        ];
        $json = $this->load("package.json");
        foreach ($buildScripts as $script) {
            if (isset($json->scripts->$script)) {
                $this->exec("npm run $script");
                break;
            }
        }
    }
}
