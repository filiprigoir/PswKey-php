<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

use PswKey\Core\Modifiers\DerivationProfile;

/**
* Configuration for Base64 encoding and decoding
*/
trait Base64Char {
    
    protected array $_base64;
    protected array $_base64Reverse;
    protected ?string $_base64Str = null;
    protected ?array $_baseConfig64 = null;

    protected function lazyLoading_baseConfig64() : void {
        if($this->_baseConfig64 == null) {
            $this->_baseConfig64 = [
                'checksum' => false,
                'bindingEncode' => '_base64',
                'bindingDecode' => '_base64Reverse',
                'bindingStr' => '_base64Str',
                'context' => DerivationProfile::DERIVATION_CHARSET . '064',
                'process'=> 'bitshift',
                'bitmask' => 0x3F,
                'base' => 64,
                'bits' => 6,
                'block' => 86
            ];
        }
    }
}