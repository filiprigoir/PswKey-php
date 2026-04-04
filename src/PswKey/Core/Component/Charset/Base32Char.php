<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

/**              
* Inject Base32 string-methods
*/
trait Base32Char {
    
    //Base32 array table to search in
    protected array $_base32;

    //Custom reverse table
    protected array $_base32Reverse;

    //Base32string to check in
    protected ?string $_base32Str = null;

    //Configuration base32
    protected ?array $_baseConfig32 = null;

    protected function lazyLoading_baseConfig32() : void {
        if($this->_baseConfig32 == null) {
            $this->_baseConfig32 = [
                'checksum' => false,
                'bindingEncode' => '_base32',
                'bindingDecode' => '_base32Reverse',
                'bindingStr' => '_base32Str',
                'context' => 'Chars_32',
                'process'=> 'bitshift',
                'bitmask' => 0x1F,
                'base' => 32,
                'bits' => 5,
                'block' => 103
            ];
        }
    }
}