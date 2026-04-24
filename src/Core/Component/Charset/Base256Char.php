<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

use PswKey\Core\Modifiers\ShuffleProfile;

/**              
* Inject Base256 string-methods
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
                'context' => ShuffleProfile::DERIVATION_STANDARD . '256',
                'process'=> 'precompute',
                'base' => 256,
                'block' => 64
            ];
        }
    }
}