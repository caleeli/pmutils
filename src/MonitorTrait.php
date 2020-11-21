<?php

namespace David\PmUtils;

trait MonitorTrait
{
    public function watch()
    {
        $log = realpath(__DIR__ . '/../log') . '/pmtools.log';
        if (!file_exists($log)) {
            file_put_contents($log, '');
            chmod($log, 0777);
        }
        $f = fopen($log, 'a');
        while(true) {
            foreach(glob(__DIR__ . '/../queue/*.json') as $j) {
                $json = json_decode(file_get_contents($j));
                unlink($j);
                if ($json->command) {
                    fwrite($f, "> {$json->command}\n");
                    shell_exec($json->command . ' >> ' . $log . ' 2>&1');
                }
            }
            sleep(5);
        }
    }
}
