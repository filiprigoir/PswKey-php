<?php declare(strict_types=1);

namespace PswKey\Util\Secure;

use PswKey\Exception\InputException;
use SensitiveParameter;

class OTP {

    private function __construct() {}
  
    public static function matrixTable() : array {
        //Lazy Loading
        static $matrixTable = null;
        if($matrixTable === null) {
            $matrixTable = [
                [0, 9, 8, 7, 6, 5, 4, 3, 2, 1],
                [1, 0, 9, 8, 7, 6, 5, 4, 3, 2],
                [2, 1, 0, 9, 8, 7, 6, 5, 4, 3],
                [3, 2, 1, 0, 9, 8, 7, 6, 5, 4],
                [4, 3, 2, 1, 0, 9, 8, 7, 6, 5],
                [5, 4, 3, 2, 1, 0, 9, 8, 7, 6],
                [6, 5, 4, 3, 2, 1, 0, 9, 8, 7],
                [7, 6, 5, 4, 3, 2, 1, 0, 9, 8],
                [8, 7, 6, 5, 4, 3, 2, 1, 0, 9],
                [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
            ];            
        }
        return $matrixTable;
    }

    public static function digits(#[SensitiveParameter] string &$switchIds, #[SensitiveParameter] string &$secretIds) : ?string {

        if(strlen($secretIds) < strlen($switchIds)) {
            throw new \LengthException('Second argument must be equal or more than first argument');
        }

        $matrixTable = self::matrixTable();
        $switched = '';
        $buffer = [];
        $block = 128;
        $increase = $block;
        $index = -1;
        for ($i=0; $i < \strlen($switchIds); $i++) { 

            $buffer[++$index] = $matrixTable[$secretIds[$i]][$switchIds[$i]] ?? null;
            if($buffer[$index] === null) {
                throw new InputException("only digits 0 to 9 are excepted for onetimepad::digits()");
            }
            
            if($i >= $increase) {
                $switched .= \implode('', $buffer);
                $buffer = [];
                $increase += $block;
                $index = -1;            
            }
        }

        if(\count($buffer) > 0) $switched .= \implode('', $buffer);
        return $switched;
    }

    public static function bytes(#[SensitiveParameter] string &$switchBytes, #[SensitiveParameter] string &$secretBytes) : string {
        return $switchBytes ^ $secretBytes;
    }
}