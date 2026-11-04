<?php declare(strict_types=1);

namespace PswKey\Util\Math;

class Calculation {

    public static $initSize = 7; //default 64-bit, otherwise 3 = 32-bit

    public static function bytesToDec(string $bytes): string {
        $step = self::$initSize;
        $dec  = '0';
        for ($i = 0, $len = strlen($bytes); $i < $len; $i += $step) {
            $part = substr($bytes, $i, $step);
            $val  = 0;
            foreach (unpack('C*', $part) as $b) { $val = ($val << 8) | $b; }
            $dec = bcadd(bcmul($dec, (string) (256 ** strlen($part)), 0), (string) $val, 0);
        }
        return $dec;
    }

    public static function decToBytes(string $dec): string {
        $step = self::$initSize;
        $mod  = (string) (256 ** $step);
        $groups = [];
        while(bccomp($dec, '0', 0) > 0) {
            $rest = (int) bcmod($dec, $mod, 0);
            $dec  = bcdiv($dec, $mod, 0);
            $g = '';
            for ($j = 0; $j < $step; $j++) { $g = chr($rest & 0xFF) . $g; $rest >>= 8; }
            $groups[] = $g;
        }
        return ltrim(implode('', array_reverse($groups)), "\0");
    }
    
    public static function checkLength(int $length) : int {
	    return min($length / 2, 64);
    }

    public static function getLastBits(int|float $digits, int|float $snip): int {
        return $digits & (1 << $snip) - 1;
    }

    public static function getFirstBits(int|float $digits, int|float $snip, int $fixedBits): int {
        return ($digits >> $fixedBits-$snip) & (1 << $snip) - 1;
    }

    public static function getFactor(int $length) : int|float {
        $factor = 1.3 + ($length / 256) * 0.25;
        return ceil($length * $factor);
    }
}

