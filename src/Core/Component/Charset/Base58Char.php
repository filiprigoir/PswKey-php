<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

use PswKey\Core\Modifiers\ShuffleProfile;

/**
* Configuration for Base58 encoding and decoding
*/
trait Base58Char {
    
    protected array $_base58;
    protected array $_base58Reverse;
    protected ?string $_base58Str = null;
    protected ?array $_baseConfig58 = null;

    protected function lazyLoading_baseConfig58() : void {
        if($this->_baseConfig58 == null) {
            $this->_baseConfig58 = [
                'checksum' => false,
                'bindingEncode' => '_base58',
                'bindingDecode' => '_base58Reverse',
                'bindingStr' => '_base58Str',
                'context' => ShuffleProfile::DERIVATION_CHARSET . '058',
                'process'=> 'compute',
                'exponentiation' => ["chunk" => 14, "exp" => 7,"symbol" => 8, "init" => [
                        2207984167552, 38068692544, 656356768,
                        11316496, 195112, 3364, 58, 1
                    ]
                ],
                'base' => 58
            ];
        }
    }
}