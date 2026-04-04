<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Custom;

/**  
* Inject Custom base string-methods
*/
trait Custom256To {

    //Custom search table with range posibility from 4 to 256 bytes
    protected array $_custom256To;

    //Custom reverse table with range posibility from 4 to 256 bytes
    protected array $_custom256ToReverse;

    //Custom string to check in
    protected ?string $_custom256ToStr = null;

    //Configuration Custom random base
    protected ?array $_customConfig256To = null;

    protected function lazyLoading_customConfig256To() : void {
        if($this->_customConfig256To == null) {
            $this->_customConfig256To = [
                'checksum' => false,
                'bindingEncode' => '_custom256To',
                'bindingDecode' => '_custom256ToReverse',
                'bindingStr' => '_custom256ToStr',
                'context' => 'MyCustom',
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

    //contract to implement
    abstract function customTo(array $singleBytes, int $requiredLength, bool $shuffle = true) : self;
}