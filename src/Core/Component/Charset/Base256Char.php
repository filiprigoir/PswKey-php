<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

use PswKey\Core\Modifiers\DerivationProfile;

/**
* Configuration for Base256 encoding and decoding
*/
trait Base256Char {
    
    protected ?array $_base256 = null;
    protected ?array $_base256Reverse = null;
    protected ?array $_baseConfig256 = null;
    protected ?string $_base256Str = null;

    protected function lazyLoading_baseConfig256() : void {
        if($this->_baseConfig256 == null) {
            $this->_baseConfig256 = [
                'checksum' => true,
                'bindingEncode' => '_base256',
                'bindingDecode' => '_base256Reverse',
                'bindingStr' => '_base256Str',
                'context' => DerivationProfile::DERIVATION_CHARSET . '256',
                'process'=> 'precompute',
                'base' => 256,
                'block' => 64
            ];
        }
    }
}