<?php

namespace David\PmUtils;

class Console
{
    private $composer;

    public function __construct($composer)
    {
        $this->composer = $composer;
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
            ['\\', '  '],
            json_encode($this->composer, JSON_PRETTY_PRINT)
        );
        //file_put_contents('composer.json', $json);
        echo $json;
    }

    public function install($name)
    {
        $this->print("Install package: $name");
        $this->add_repo($name);
    }

    public function add_repo($name)
    {
        if (!$this->find_repo($name)) {
            $this->composer->repositories[] = (object) [
                'type' => 'path',
                'url' => "../{$name}",
            ];
        }
        $this->save();
    }

    public function find_repo($name)
    {
        var_dump($this->composer);
        foreach($this->composer->repositories as $repo) {
            if (substr($repo->url, -strlen($name)) === $name) {
                return $repo;
            }
        }
        return null;
    }
}
