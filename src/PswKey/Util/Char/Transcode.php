<?php declare(strict_types=1);

namespace PswKey\Util\Char;

class Transcode {

    private function __construct() {}
    
    public static function getUTF(string $iso, int $bits = 8) : string {
        return \UConverter::transcode($iso,"UTF-{$bits}","ISO-8859-1");
    }

    public static function getISO(string $utf, int $bits = 8) : string {
        return \UConverter::transcode($utf,"ISO-8859-1","UTF-{$bits}");
    }
}