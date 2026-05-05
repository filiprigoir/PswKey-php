<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Custom;

use PswKey\Core\Modifiers\DerivationProfile;

/**  
* Configuration for baseX encoding
* Custom base is a user defined baseTo with range from 4 to 256 bytes
*/
trait Custom256To {

    protected array $_custom256To;
    protected array $_custom256ToReverse;
    protected ?string $_custom256ToStr = null;
    protected ?array $_customConfig256To = null;

    protected function lazyLoading_customConfig256To() : void {
        if($this->_customConfig256To == null) {
            $this->_customConfig256To = [
                'checksum' => false,
                'bindingEncode' => '_custom256To',
                'bindingDecode' => '_custom256ToReverse',
                'bindingStr' => '_custom256ToStr',
                'context' => DerivationProfile::DERIVATION_CUSTOM . 256,
                'process'=> 'compute',
                'bitmask' => [
                    '2' => 0x03, '3' => 0x07, '4' => 0x0F, '5' => 0x1F, 
                    '6' => 0x3F, '7' => 0x7F, '8' => 0xFF
                ],
                'exponentiation' => null,
                'base' => null,
                'bits' => null
            ];
        }
    }

    abstract function customTo(array $singleBytes, int $requiredLength, bool $shuffle = true) : self;
}