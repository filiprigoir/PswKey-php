<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

/**              
* Inject Base64 string-methods
*/
trait Base64Char {
    
    //Base64 array table to search in
    protected array $_base64;

    //Custom reverse table
    protected array $_base64Reverse;

    //Base64 string to check in
    protected ?string $_base64Str = null;

    //Configuration base64
    protected ?array $_baseConfig64 = null;

    protected function lazyLoading_baseConfig64() : void {
        if($this->_baseConfig64 == null) {
            $this->_baseConfig64 = [
                'checksum' => false,
                'bindingEncode' => '_base64',
                'bindingDecode' => '_base64Reverse',
                'bindingStr' => '_base64Str',
                'context' => 'Chars_64',
                'process'=> 'bitshift',
                'bitmask' => 0x3F,
                'base' => 64,
                'bits' => 6,
                'block' => 86
            ];
        }
    }
}