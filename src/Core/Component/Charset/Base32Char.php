<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

use PswKey\Core\Modifiers\ShuffleProfile;

/**
* Configuration for Base32 encoding and decoding
*/
trait Base32Char {
    
    protected array $_base32;
    protected array $_base32Reverse;
    protected ?string $_base32Str = null;
    protected ?array $_baseConfig32 = null;

    protected function lazyLoading_baseConfig32() : void {
        if($this->_baseConfig32 == null) {
            $this->_baseConfig32 = [
                'checksum' => false,
                'bindingEncode' => '_base32',
                'bindingDecode' => '_base32Reverse',
                'bindingStr' => '_base32Str',
                'context' => ShuffleProfile::DERIVATION_CHARSET . '032',
                'process'=> 'bitshift',
                'bitmask' => 0x1F,
                'base' => 32,
                'bits' => 5,
                'block' => 103
            ];
        }
    }
}