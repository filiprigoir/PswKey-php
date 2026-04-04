<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Custom;

/**              
* Inject Custom base string-methods
*/
trait Custom256From {
  
    //Custom search table with range posibility from 4 to 256 bytes
    protected array $_custom256From;

    //Custom reverse table with range posibility from 4 to 256 bytes
    protected array $_custom256FromReverse;

    //Custom string to check in
    protected ?string $_custom256FromStr = null;

    //Configuration Custom random base
    protected ?array $_customConfig256From = null;

    protected function lazyLoading_customConfig256From() : void {
        if($this->_customConfig256From == null) {
            $this->_customConfig256From = [
                'checksum' => false,
                'bindingEncode' => '_custom256From',
                'bindingDecode' => '_custom256FromReverse',
                'bindingStr' => '_custom256FromStr',
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
    abstract function customFrom(array $singleBytes, int $requiredLength, bool $shuffle = true) : self;
}