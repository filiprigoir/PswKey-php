<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

/**              
* Inject Base62 string-methods
*/
trait Base62Char {
    
    //Base62 array table to search in
    protected array $_base62;

    //Custom reverse table
    protected array $_base62Reverse;

    //Base62 string to check in
    protected ?string $_base62Str = null;

    //Configuration base62
    protected ?array $_baseConfig62 = null;

    protected function lazyLoading_baseConfig62() : void {
        if($this->_baseConfig62 == null) {
            $this->_baseConfig62 = [
                'checksum' => false,
                'bindingEncode' => '_base62',
                'bindingDecode' => '_base62Reverse',
                'bindingStr' => '_base62Str',
                'context' => 'Chars_62',
                'process'=> 'compute',
                'exponentiation' => ["chunk" => 14, "exp" => 7,"symbol" => 8, "init" => [
                        3521614606208, 56800235584, 916132832,
                        14776336, 238328, 3844, 62, 1
                    ]
                ],
                'base' => 62,
                'bits' => 6
            ];
        }
    }
}