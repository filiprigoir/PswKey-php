<?php declare(strict_types=1);

namespace PswKey\Util\Math;

class Calculation {
    public static function bytesToDec(string $bytes): string {
        
        $dec = '0';
        $len = strlen($bytes);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcadd(bcmul($dec, '256'), (string)ord($bytes[$i]));
        }
        return $dec;
    }

    public static function decToBytes(string $dec): string
    {
        $str = "";
        while (bccomp($dec, '0') > 0) {
            $str .= chr((int)bcmod($dec, '256'));
            $dec = bcdiv($dec, '256', 0);
        }
        return strrev($str);
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

