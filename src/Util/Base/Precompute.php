<?php declare(strict_types=1);

namespace PswKey\Util\Base;

class Precompute {

    private function __construct() {}

        public static function initBase(int $base) : ?array {

        //Check ranges
        $result = match(true) {
            $base >= 12 && $base <= 14 => ["chunk"=> 14,"exp"=> 12,"symbol"=> 13],
            $base >= 15 && $base <= 17 => ["chunk"=> 14,"exp"=> 11,"symbol"=> 12],
            $base >= 20 && $base <= 23 => ["chunk"=> 14,"exp"=> 10 ,"symbol"=> 11],
            $base >= 28 && $base <= 31 => ["chunk"=> 14,"exp"=> 9,"symbol"=> 10],
            $base >= 32 && $base <= 35 => ["chunk"=> 15,"exp"=> 9,"symbol"=> 10],
            $base >= 36 && $base <= 42 => ["chunk"=> 14,"exp"=> 8,"symbol"=> 9],
            $base >= 43 && $base <= 46 => ["chunk"=> 14,"exp"=> 8,"symbol"=> 9],
            $base >= 47 && $base <= 56 => ["chunk"=> 15,"exp"=> 8,"symbol"=> 9],
            $base >= 57 && $base <= 71 => ["chunk"=> 14,"exp"=> 7,"symbol"=> 8],
            $base >= 72 && $base <= 74 => ["chunk"=> 14,"exp"=> 7,"symbol"=> 8],
            $base >= 75 && $base <= 99 => ["chunk"=> 15,"exp"=> 7,"symbol"=> 8],
            $base >= 100 && $base <= 146 => ["chunk"=> 14,"exp"=> 6,"symbol"=> 7],
            $base >= 147 && $base <= 215 => ["chunk"=> 15,"exp"=> 6,"symbol"=> 7],
            $base >= 216 && $base <= 251 => ["chunk"=> 13,"exp"=> 5,"symbol"=> 6],
            $base >= 252 && $base <= 256 => ["chunk"=> 14,"exp"=> 5,"symbol"=> 6],
            
            //Lazy-loaded table for exceptions
            default => (function() use ($base) {
                static $exception = [
                    4 => ["chunk"=> 14,"exp"=> 23,"symbol"=> 24],
                    5 => ["chunk"=> 14,"exp"=> 20,"symbol"=> 21],
                    6 => ["chunk"=> 14,"exp"=> 17,"symbol"=> 18],
                    7 => ["chunk"=> 14,"exp"=> 16,"symbol"=> 17],
                    8 => ["chunk"=> 14,"exp"=> 15,"symbol"=> 16],
                    9 => ["chunk"=> 14,"exp"=> 14,"symbol"=> 15],
                    10 => ["chunk"=> 14,"exp"=> 13,"symbol"=> 14],
                    11 => ["chunk"=> 14,"exp"=> 13,"symbol"=> 14],
                    18 => ["chunk"=> 15,"exp"=> 11,"symbol"=> 12],
                    19 => ["chunk"=> 14,"exp"=> 10,"symbol"=> 11],
                    24 => ["chunk"=> 15,"exp"=> 10,"symbol"=> 11],
                    25 => ["chunk"=> 15,"exp"=> 10,"symbol"=> 11],
                    26 => ["chunk"=> 14,"exp"=> 9,"symbol"=> 10],
                    27 => ["chunk"=> 14,"exp"=> 9,"symbol"=> 10]
                ];
                return $exception[$base] ?? null;
            })()
        };
        
        return $result;
    }

    public static function isBitshift(int $base) : ?array {
        return match ($base) {
            4 => ['bits' => 2, 'bitmask' => 0x03, 'block' => 256],
            8 => ['bits' => 3, 'bitmask' => 0x07, 'block' => 171],
            16 => ['bits' => 4, 'bitmask' => 0x0F, 'block' => 128],
            32 => ['bits' => 5, 'bitmask' => 0x1F, 'block' => 103],
            64 => ['bits' => 6, 'bitmask' => 0x3F, 'block' => 86],
            128 => ['bits' => 7, 'bitmask' => 0x7F, 'block' => 74],
            default => null
        };
    }
}