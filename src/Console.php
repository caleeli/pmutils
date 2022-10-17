<?php

namespace David\PmUtils;

use David\PmUtils\Traits\DBTrait;
use David\PmUtils\Traits\PMTrait;
use Exception;
use PDO;
use ReflectionMethod;
use ReflectionObject;

class Console
{
    use MonitorTrait;
    use DBTrait;
    use PMTrait;

    public $composer;
    public $composer_filename;

    public function __construct($filename)
    {
        $this->composer_filename = realpath($filename);
        $this->composer = $this->load($filename);
    }

    /**
     * Queue a command
     *
     */
    private function queueCommand($command)
    {
        $path = __DIR__ . '/../queue/' . \time() . \rand() . '.json';
        $json = \json_encode([
            'command' => $command,
        ]);
        \file_put_contents($path, $json);
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
    public function install($package, $version = null)
    {
        $this->print("Install package: $package $version");
        $this->add_repo($package, $version);
        $pack = $this->pack_name($package);
        // shell escape version
        $version = escapeshellarg($version);
        echo("composer require $pack" . ($version ? ":$version" : '') . "\n");
        passthru("composer require $pack" . ($version ? "=$version" : ''));
        $install = explode('/', $pack)[1] . ':install';
        $this->print("php artisan $ ");
        passthru("php artisan $install");
        // reload composer content after install package
        $this->composer = $this->load($this->composer_filename);
    }

    /**
     * Instala los paquetes base de PM4
     *
     * @return void
     */
    public function install_base($version=null)
    {
        // Add package-savedsearch dev repo
        $this->add_repo('packages', $version);
        $this->add_repo('docker-executor-node-ssr', $version);
        $this->add_repo('connector-send-email', $version);
        $this->add_repo('package-data-sources', $version);
        $this->add_repo('package-collections', $version);
        $this->add_repo('package-savedsearch', $version);
        $this->add_repo('package-versions', $version);
        $this->composer = $this->load($this->composer_filename);

        $this->install('packages', $version);
        $this->install('docker-executor-node-ssr', $version);
        $this->install('connector-send-email', $version);
        $this->install('package-data-sources', $version);
        $this->install('package-collections', $version);
        $this->install('package-savedsearch', $version);
        $this->install('package-versions', $version);
    }

    /**
     * Instala los paquetes base del repositorio en github
     *
     * @return void
     */
    public function git_base($branch = 'develop')
    {
        exec('rm -rf bootstrap/cache/*');
        // get versions for 4.1-develop
        $version = $this->package_versions($branch);
        // Add base packages
        $this->add_github_repo('packages');
        $this->add_github_repo('docker-executor-node-ssr');
        $this->add_github_repo('connector-send-email');
        $this->add_github_repo('package-data-sources');
        $this->add_github_repo('package-collections');
        $this->add_github_repo('package-savedsearch');
        $this->add_github_repo('package-versions');
        $this->composer = $this->load($this->composer_filename);

        $this->install('packages', $version['packages']);
        $this->install('docker-executor-node-ssr', $version['docker-executor-node-ssr']);
        $this->install('connector-send-email', $version['connector-send-email']);
        $this->install('package-data-sources', $version['package-data-sources']);
        $this->install('package-collections', $version['package-collections']);
        $this->install('package-savedsearch', $version['package-savedsearch']);
        $this->install('package-versions', $version['package-versions']);
    }



    public function package_versions($branch='develop')
    {
        // UPDATE composer.json
        exec('cd '.__DIR__.'/../packages && git checkout "origin/' . $branch . '" composer.json');
        $file = __DIR__.'/../packages/composer.json';
        $json = json_decode(file_get_contents($file), true);
        $version = $json['extra']['processmaker']['enterprise'];
        $version['packages'] = 'dev-' . $branch;
        return $version;
    }

    public function add_github_repo($name)
    {
        if (!$this->find_repo($name)) {
            // add git repository
            $this->composer->repositories[] = (object) [
                'type' => 'git',
                'url' => "https://github.com/ProcessMaker/{$name}.git"
            ];
            $this->save();
        }
    }

    public function add_repo($name, $version = null)
    {
        if (!$version && !$this->find_repo($name)) {
            $path = "../../workspace/{$name}";
            if (!file_exists($path)) {
                $path = "../../projects/{$name}";
            }
            if (!file_exists($path)) {
                return $this->add_github_repo($name);
            }
            // set path repository
            $this->set_repo($name, (object) [
                'type' => 'path',
                'url' => $path,
            ]);
            $this->save();
        } else {
            $this->add_github_repo($name);
        }
    }

    private function set_repo($name, $config)
    {
        // Find and update by $name in repositories
        if ($repo = $this->find_repo($name)) {
            $repo->url = $config->url;
            $repo->type = $config->type;
        } else {
            // Add new repository
            $this->composer->repositories[] = $config;
        }
    }

    public function pack_name($name)
    {
        $path = "../../workspace/$name/composer.json";
        if (!file_exists($path)) {
            $path = "../../projects/$name/composer.json";
        }
        if (!file_exists($path)) {
            return "processmaker/{$name}";
        }
        $composer = $this->load($path);
        return $composer->name;
    }

    public function find_repo($name)
    {
        if (!isset($this->composer->repositories)) {
            $this->composer->repositories = [];
        }
        foreach ($this->composer->repositories as $repo) {
            if (strpos($repo->url, "/$name") !== false) {
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

    /**
     * Returns true if the database is online and installed
     */
    public function start_pm4()
    {
        // TRY TO CONNECT TO THE DATABASE
        $this->tryTo(function () {
            new PDO('mysql:host=localhost:3307;dbname=workflow', 'root', 'root');
            \error_log("Database is online");
            return true;
        }, function (Exception $e) {
            \error_log($e->getMessage());
            // Connection refused
            if (strpos($e->getMessage(), 'Connection refused') !== false) {
                $this->queueCommand("service pm4_mysql restart");
                return true;
            }
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $pmHome = $_ENV['PROCESSMAKER_HOME'];
                $this->queueCommand("./pmtools migrate {$pmHome}");
                return true;
            }
            return false;
        });
        // RESTART HORIZON AND QUEUE
        $pmHome = $_ENV['PROCESSMAKER_HOME'];
        $this->queueCommand("cd {$pmHome};php artisan horizon:terminate;service pm4_horizon stop;php artisan optimize:clear;service pm4_horizon start;");
        $this->queueCommand("service pm4_queue restart");
    }

    private function tryTo(callable $callable, callable $catch, $times = 3, $sleep = 5)
    {
        $i = 0;
        while ($i < $times) {
            try {
                return $callable();
            } catch (Exception $e) {
                $catch($e);
                $i++;
                sleep($sleep);
            }
        }
        throw $e;
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
