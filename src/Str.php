<?php

namespace David\PmUtils;

class Str
{
    public static function plural($singular)
    {
        $last_letter = strtolower($singular[strlen($singular)-1]);
        switch($last_letter) {
            case 'y':
                return substr($singular,0,-1).'ies';
            case 's':
                return $singular.'es';
            default:
                return $singular.'s';
        }
    }

    public static function camel($string)
    {
        return \preg_replace('/[\s\-_]+/', '', \ucwords($string, "\t\n\r\f\v -_."));
    }
}