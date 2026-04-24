<?php declare(strict_types=1);

namespace PswKey\Util\Base;

class InitString {

    private function __construct() {}

    public static function _base100() : string {
        static $base100 = null;
        if($base100 === null) {
            $base100 = "\x31\x32\x33\x34\x35\x36\x37\x38\x39\x41\x42\x43\x44\x45\x46\x47\x48\x4a\x4b\x4c\x4d\x4e\x50\x51\x52\x53"
                . "\x54\x55\x56\x57\x58\x59\x5a\x61\x62\x63\x64\x65\x66\x67\x68\x69\x6a\x6b\x6d\x6e\x6f\x70\x71\x72\x73\x74\x75"
                . "\x76\x77\x78\x79\x7a\x30\x49\x4f\x6c\x2f\x2b\x21\x22\x23\x24\x25\x26\x27\x28\x29\x2a\x2c\x2d\x2e\x3a\x3b\x3d"
                . "\x3f\x40\x5b\x5c\x5d\x5e\x5f\x60\x7b\x7c\x7d\x7e\xa3\xa7\xa8\xb2\xb3\xb4\xb5\xb0";
        }
        return $base100;
    }

    /**
     * The system uses a 62-character alphabet.
     * Final reduction to 32 characters is performed within the Core pipeline.
     *
     * This is intentional and part of the design.
     */
    public static function _base32() : string {
        return substr(self::_base100(), 0, 62);
    }

    public static function _base58() : string {
        return substr(self::_base100(), 0, 58);
    }

    public static function _base62() : string {
        return substr(self::_base100(), 0, 62);
    }

    public static function _base64() : string {
        return substr(self::_base100(), 0, 64);
    }

    public static function _base256() : string {
        return self::_base100();
    }
}