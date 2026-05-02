<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

use PswKey\Core\Modifiers\ShuffleProfile;

/**
* Configuration for Base100 encoding and decoding
*/
trait Base100Char {
    
    protected array $_base100;
    protected array $_base100Reverse;
    protected ?string $_base100Str = null;
    protected ?array $_baseConfig100 = null;
    protected ?array $_baseConfig10 = null;

    protected function lazyLoading_baseConfig100() : void {
        if($this->_baseConfig100 == null) {
            $this->_baseConfig100 = $this->baseConfig100();
            $this->_baseConfig100['base'] = 100;
        }
    }

    protected function lazyLoading_baseConfig10() : void {
        if($this->_baseConfig10 == null) {
            $this->_baseConfig10 = $this->baseConfig100();
            $this->_baseConfig10['base'] = 10;
        }
    }

    private function baseConfig100() : array {
        return [
            'checksum' => true,
            'bindingEncode' => '_base100',
            'bindingDecode' => '_base100Reverse',
            'bindingStr' => '_base100Str',
            'context' => ShuffleProfile::DERIVATION_CHARSET . '100',
            'process'=> 'precompute',
            'block' => 64
        ];
    }
}