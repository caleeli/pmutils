<?php

namespace David\PmUtils\Traits;

use David\PmUtils\Str;

trait TPLTrait
{
    private function createTemplate($templateName)
    {
        return $this->processFolder(__DIR__ . "/../../templates/{$templateName}");
    }

    private function execTemplate(array $tpl, $data)
    {
        foreach ($tpl as $row) {
            $file = $this->evaluateTpl($row[0], $data);
            if (\dirname($file) && !\file_exists(\dirname($file))) {
                \mkdir(\dirname($file), 0775, \true);
            }
            file_put_contents($file, $this->evaluateTpl($row[1], $data));
        }
    }

    private function processFile($file, $base, $tpl)
    {
        $content = file_get_contents($file);
        if ((strpos($file, '<'.'<')!==false && strpos($file, '>'.'>')!==false)
            || (strpos($content, '<'.'<')!==false && strpos($content, '>'.'>')!==false)
        ) {
            $tpl[]=[substr($this->buildTpl($file), \strlen($base)), $this->buildTpl($content)];
        }
        return $tpl;
    }

    private function buildTpl($content)
    {
        $content = str_replace([
            '<'.'?=',
            '<'.'?php',
            '?'.'>',
        ], [
            '~111~',
            '~222~',
            '~333~',
        ], $content);
        $content = str_replace([
            '~111~',
            '~222~',
            '~333~',
        ], [
            '<?=\'<?=\'?>',
            '<?=\'<?php\'?>',
            '<?=\'?>\'?>',
        ], $content);
        $content = preg_replace_callback('/(\s*)<<</', function ($match) {
            $leftAlign = $match[1];
            return '<?php $this->leftAlign = '.var_export($leftAlign, true).';ob_start(); ';
        }, $content);
        $content = str_replace([
            '>'.'>'.'>',
            '<'.'<',
            '>'.'>',
        ], [
            "\n" . '$this->alignEndFlush(ob_get_contents()); ?'.'>',
            '<'.'?=',
            '?'.'>',
        ], $content);
        return $content;
    }

    private function processFolder($path='', $base = '', $tpl=[])
    {
        if (!$base) $base = $path . '/';
        $white = ['app', 'resources', 'database'];
        $black = ['node_modules', 'vendor', 'coverage', 'storage'];
        foreach (glob($path ? "$path/*" : '*') as $file) {
            if ($path === '' && !in_array($file, $white)) {
                continue;
            }
            if ($path === '' && in_array($file, $black)) {
                continue;
            }
            if (is_file($file)) {
                $tpl = $this->processFile($file, $base, $tpl);
            } else {
                $tpl = $this->processFolder($file, $base, $tpl);
            }
        }
        return $tpl;
    }

    private function evaluateTpl($__tt, $__data)
    {
        $date_time = date('Y_m_d_His');
        $__line__ = 0;
        extract($__data);
        ob_start();
        eval("?".">$__tt");
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    private function alignEndFlush($text)
    {
        ob_end_clean();
        $res = str_replace("\n", $this->leftAlign, "\n" . trim($text));
        echo $res, "\n";
    }
}
