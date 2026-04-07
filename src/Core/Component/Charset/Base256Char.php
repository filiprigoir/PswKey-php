<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

/**              
* Inject Base256 string-methods
*/
trait Base256Char {
    
    //Base256 array table to search in
    protected ?array $_base256 = null;

    //Custom reverse table
    protected ?array $_base256Reverse = null;

    //Configuration base100
    protected ?array $_baseConfig256 = null;

    //Base256 string to check in
    protected ?string $_base256Str = null;

    protected function lazyLoading_baseConfig256() : void {
        if($this->_baseConfig256 == null) {
            $this->_baseConfig256 = [
                'checksum' => true,
                'bindingEncode' => '_base256',
                'bindingDecode' => '_base256Reverse',
                'bindingStr' => '_base256Str',
                'context' => 'Chars256',
                'process'=> 'precompute',
                'base' => 256,
                'block' => 64
            ];
        }
    }
}