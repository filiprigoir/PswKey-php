<?php
declare(strict_types=1);

namespace PswKey\Core\Component\Charset;

/**              
* Inject Base58 string-methods
*/
trait Base58Char {
    
    //Base58 array table to search in
    protected array $_base58;

    //Custom reverse table
    protected array $_base58Reverse;

    //Base58 string to check in
    protected ?string $_base58Str = null;

    //Configuration base58
    protected ?array $_baseConfig58 = null;

    protected function lazyLoading_baseConfig58() : void {
        if($this->_baseConfig58 == null) {
            $this->_baseConfig58 = [
                'checksum' => false,
                'bindingEncode' => '_base58',
                'bindingDecode' => '_base58Reverse',
                'bindingStr' => '_base58Str',
                'context' => 'Chars_58',
                'process'=> 'compute',
                'exponentiation' => ["chunk" => 14, "exp" => 7,"symbol" => 8, "init" => [
                        2207984167552, 38068692544, 656356768,
                        11316496, 195112, 3364, 58, 1
                    ]
                ],
                'base' => 58,
                'bits' => 6
            ];
        }
    }
}