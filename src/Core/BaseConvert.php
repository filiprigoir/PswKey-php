<?php
declare(strict_types=1);

namespace PswKey\Core;

use PswKey\Core\Component\Charset\Base100Char;
use PswKey\Core\Component\Charset\Base256Char;
use PswKey\Core\Component\Charset\Base32Char;
use PswKey\Core\Component\Charset\Base58Char;
use PswKey\Core\Component\Charset\Base62Char;
use PswKey\Core\Component\Charset\Base64Char;
use PswKey\Core\Component\Custom\Custom256From;
use PswKey\Core\Component\Custom\Custom256To;
use PswKey\Exception\InputException;
use PswKey\Util\Base\InitArray;
use PswKey\Util\Base\InitString;
use PswKey\Util\Char\Prefix;
use PswKey\Util\Math\Calculation;
use PswKey\Util\Secure\MemeZero;
use PswKey\Validator\InputHandlerCharacter;

/**
 * Convertion of any base and custom single bytes chars
 */
abstract class BaseConvert {

    //Check availability GMP
    protected bool $_gmp = true;

    //Binding properties
    protected ?string $_from = null;
    protected ?string $_to = null;

    //Set to false, chunks are limited to 22 bytes; set to true, chunks are up to 169 bytes as a big-endian integer
    public bool $longEndianChunk = true;

    //Intermediate help properties
    protected ?array $_base256Int = null;
    protected ?array $_base256IntReverse = null;
    protected ?array $_base100Dimensional = null;

    //Components
    use InputHandlerCharacter, Base32Char, Base58Char, Base62Char, 
        Base64Char, Base100Char, Base256Char, Custom256From, Custom256To;

    public function __construct() {}

    /**** Protected Methods ****/

    protected function precompute100Precompute10(string $singleBytes, array $configFrom, array $configTo) : ?string { 
        
        //Sample-check of single bytes
        $len = \strlen($singleBytes);
        $quickCheck = $this->checkBase($singleBytes, $len, InitString::_base100(), $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in precompute100Precompute10() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
     
            return null; 
        }

        $blockLength = 64;
        $left = $len % $blockLength;

        $converted = '';
        $buffer = [];
        $index = -1;
        for($i=0; $i < $len; $i++) { 

            if($i === $blockLength) {
                $converted .= implode('', $buffer);
                $index = -1;
                $blockLength += 64;
                $buffer = [];
            }

            $buffer[++$index] = $this->_base100Reverse[$singleBytes[$i]] ?? null;
            if($buffer[$index] === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function password2Int() (char: ' .  \mb_substr($singleBytes, $pos, Prefix::byteLength($singleBytes[$pos])) . " positie: " . ($pos) + 1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

                return null;
            }
        }

        if($left > 0) $converted .= implode('', $buffer);

        return $converted;
    }
    
    protected function precompute10Precompute100(string $digitPairs, array $configFrom, array $configTo) : ?string { 

        $len = \strlen($digitPairs);    
        if($len % 2 === 1) {
            $this->setErrorStatus(false)
                ->setErrorMessage('The first argument must have an even digit length')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
            
            return null;
        }

        //Sampling-quickcheck
        $quickCheck = $this->checkBase($digitPairs, $len, null, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in precompute10Precompute100() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
                
            return null;   
        }

        //Prepare Dimensional array for check input digits at runtime
        if($this->_base100Dimensional === null) {
            $this->_base100Dimensional = InitArray::dimensionalIndex100();
            for ($i=0; $i < 100; $i++) {
                $index = sprintf("%02d", $i);
                $this->_base100Dimensional[$index[0]][$index[1]] = $this->_base100[$i];
            }
            MemeZero::overwriteArray($this->_base100);
        }

        $blockLength = 128;
        $converted = '';
        $buffer = [];
        $index = -1;
        for ($i=0; $i < $len-1; $i += 2) { 

            if($i >= $blockLength) {
                $converted .= implode('', $buffer);
                $index = -1;
                $blockLength += 128;
                $buffer = [];
            }

            $buffer[++$index] = $this->_base100Dimensional[$digitPairs[$i]][$digitPairs[$i+1]] ?? null;
            if($buffer[$index] === null) {
                $pos = $i;
                $this->setErrorStatus(false)
                    ->setErrorMessage('Bufferprocess found invalid input in function precompute10Precompute100() (char: ' 
                        . \mb_substr($digitPairs, $pos, Prefix::byteLength($digitPairs[$pos])) 
                        . \mb_substr($digitPairs, $pos+1, Prefix::byteLength($digitPairs[$pos+1])) 
                        . " positie: " . ($pos) + 1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
                    
                return null;
            }
        }

        if(count($buffer) > 0) $converted .= implode('', $buffer);

        return $converted;
    }

    protected function precompute256Precompute100(string $text, array $configFrom, array $configTo) : ?string { 

        if(empty($text)) {
            $this->setErrorStatus(false)
                ->setErrorMessage('At least one character must be entered')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
            
            return null;
        }

        $len = strlen($text);
        $zeros = 0;
        while (isset($text[$zeros]) && $text[$zeros] == "\0"){ 
            if($zeros > $len) break;
            $zeros++;
        };

        //Snip all chr(0) from string
        $len -= $zeros;
        $txt = $zeros > 0 ? substr($text, $zeros, null) : $text;
        $digits = $this->getEndianChunk($txt, $len); //generates a uniform big endian number

        //Convertion
        $len = strlen($digits);      
        $left = $len % 14;
        $left = $left === 0 ? 14 : $left;
        $converted = '';
        $bindingEncode = &$this->_base256;

        $buffer = [];
        $block = 128;
        $increase = $block;
        $index = 0;
        for ($i=$left; $i < $len; $i += 2) { 

            $buffer[$index++] = $bindingEncode[($digits[$i] - '0') * 10 + ($digits[$i+1] - '0')];
            if($i >= $increase) {
                $converted .= implode('', $buffer);
                $buffer = [];
                $increase += $block;
                $index = 0;
            }
        }

        if(count($buffer) > 0) $converted .= implode('', $buffer);

        $calc = \str_pad(substr($digits, 0, $left), 14, "0", STR_PAD_LEFT);

        $txtStr = "";
        for ($i=0; $i < 14; $i += 2) { 
            $txtStr .= $bindingEncode[($calc[$i] - '0') * 10 + ($calc[$i+1] - '0')];
        }

        //Get first zero-symbols 
        $symbolZeros = 0;
        while (true) {
            if($txtStr[$symbolZeros] !== $bindingEncode[0]) { //always single byte handling
                break;
            }       
            $symbolZeros++;
        }

        if($zeros > 0) {
           return str_repeat($bindingEncode[0], $zeros) . substr($txtStr, $symbolZeros, null) . $converted; 
        } 
        else {
           return substr($txtStr, $symbolZeros, null) . $converted; 
        }
    }

    protected function precompute100Precompute256(string $symbol, array $configFrom, array $configTo) : ?string {

        $len = strlen($symbol);
        $quickCheck = $this->checkBase($symbol, $len, InitString::_base100(), $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in precompute100Precompute256() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
        
            return null; 
        }

        //Grab the symbolzeros in the string
        $symbolZeros = 0;
        while (true) {
            if($symbol[$symbolZeros] !== $this->{$configTo['bindingEncode']}[0]) break; //symbolZeros
            $symbolZeros++;
        }

        $base100 = $symbolZeros > 0 ? substr($symbol, $symbolZeros, null) : $symbol; 
        $len -= $symbolZeros;
        $bindingDecode = &$this->_base256Reverse;
    
        $endianChunk = "";
        $buffer = [];
        $block = 77;
        $increase = $block;
        $index = -1;
        for ($i=0; $i < $len; $i++) { 

            $buffer[++$index] = $bindingDecode[$base100[$i]] ?? null;
            if($buffer[$index] === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function precompute100Precompute256() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " positie: " . ($pos) + 1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

                return null;
            } 
            
            if($i >= $increase) {
                $endianChunk .= implode('', $buffer);
                $buffer = [];
                $increase += $block;
                $index = -1;
            }             
        }

        if(count($buffer) > 0) $endianChunk .= implode('', $buffer);

        $converted = "";
        if($symbolZeros > 0) {
            $converted .= str_repeat("\0", $symbolZeros);
        }
        
        $converted .= $this->getText($endianChunk, ($len * 2));
        return  $converted;
    }

    protected function precompute256Precompute10(string $text, array $configFrom, array $configTo) : ?string { 

        if(empty($text)) {
            throw new InputException("At least one character must be entered"); 
        }

        $len = strlen($text);
        $zeros = 0;
        while (isset($text[$zeros]) && $text[$zeros] == "\0"){ 
            if($zeros > $len) break;
            $zeros++;
        };

        $len -= $zeros;
        $txt = $zeros > 0 ? substr($text, $zeros, null) : $text;

        //Add zerros in symbol digits
        $digits = "";
        if($zeros > 0) {
           $digits .= str_repeat("00", $zeros);
        }

        $digits .= $this->getEndianChunk($txt, $len);

        //Count Endian chunks
        $len = strlen($digits);        

        if($len % 2 === 1) {
            $digits = "0" . $digits;
            $len += 1;
        }   

        $converted = "";
        $bindingDecode = &$this->{$configTo['bindingDecode']};
        $bindengReverse = &$this->_base256IntReverse;

        if($this->_base256Int === null) {
            $bindingStr = &$this->{$configFrom['bindingStr']};
            $bindengReverse = InitArray::dimensionalIndex100();
            for ($i=0; $i < 100; $i++) { 
                $this->_base256Int[$i] = $bindingDecode[$bindingStr[$i]];

                //Reverse
                $reverse = $this->_base256Int[$i];
                $bindengReverse[$reverse[0]][$reverse[1]] = sprintf("%02d",$i);                
            }
        }

        $bindingInt = &$this->_base256Int;

        $buffer = [];
        $block = 128;
        $increase = $block;
        $index = 0;
        for ($i=0; $i < $len; $i += 2) { 
            if($i >= $increase) {
                $converted .= implode('', $buffer);
                $buffer = [];
                $increase += $block;
                $index = 0;
            }

            $buffer[++$index] = $bindingInt[($digits[$i] - '0') * 10 + ($digits[$i+1] - '0')];
        }

        if(count($buffer) > 0) $converted .= implode('', $buffer);
        
        return $converted; 
    }

    protected function precompute10Precompute256(string $digitPairs, array $configFrom, array $configTo) : ?string { 
        
        //Quick check
        $len = \strlen($digitPairs);
        $quickCheck = $this->checkBase($digitPairs, $len, null, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in precompute10Precompute256() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
     
            return null; 
        }

        if($len % 2 === 1) {
            $this->setErrorStatus(false)
                ->setErrorMessage('The first argument must have an even digit length')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
            
            return null;
        }        

        $bindingDecode = &$this->{$configFrom['bindingDecode']};
        $bindengReverse = &$this->_base256IntReverse;
        
        if($this->_base256Int === null) {
            $bindingStr = &$this->{$configTo['bindingStr']};
            $bindengReverse = InitArray::dimensionalIndex100();
            for ($i=0; $i < 100; $i++) { 
                $this->_base256Int[$i] = $bindingDecode[$bindingStr[$i]];

                //Reverse
                $reverse = $this->_base256Int[$i];
                $bindengReverse[$reverse[0]][$reverse[1]] = sprintf("%02d",$i);          
            }
        }

        //Convertion
        $converted = '';
        $buffer = [];
        $block = 128;
        $increase = $block;
        $index = -1;
        for ($i=0; $i < $len; $i += 2) {
            $buffer[++$index] = $bindengReverse[$digitPairs[$i]][$digitPairs[$i+1]] ?? null;
            if($buffer[$index] === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function password2Int() (char: ' .  \mb_substr($digitPairs, $pos, Prefix::byteLength($digitPairs[$pos])) . \mb_substr($digitPairs, $pos+1, Prefix::byteLength($digitPairs[$pos+1])) . " positie: " . ($pos) + 1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

                return null;
            } 

            if($i >= $increase) {
                $converted .= implode('', $buffer);
                $index = -1;
                $buffer = [];
                $increase += $block;
            }
        }

        if(count($buffer) > 0) $converted .= implode('', $buffer);

        //Calculate Decimal to Bytes dynamic chunk endian-number
        $len = strlen($converted);

        //Get first random zero-symbols
        $symbolZeros = 0;
        while (true) {
            if(substr($converted, $symbolZeros, 2) !== "00") {
                break;
            }
            $symbolZeros += 2;
        }

        if($symbolZeros > 0) {
            $digits = substr($converted, $symbolZeros, null);
        }
        else {
            $digits = $converted;
        }

        $len -= $symbolZeros;

        $text = "";
        if($symbolZeros > 0) {
           $text .= str_repeat("\0", (int)($symbolZeros/2)); 
        }
        
        $text .= $this->getText($digits, $len);
        return $text;
    }

    protected function precomputeBitshift100(string $symbol, array $configFrom, array $configTo) : ?string { 
        
        $len = \strlen($symbol);

        $quickCheck = $this->checkBase($symbol, $len, InitString::_base100(), $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in precompute2Bitshift() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );

            return null; 
        }       

        $buffer = 0;
        $bitsInBuffer = 0;
        $encoded = [];
        $converted = '';      

        //Variable settings
        $block = $configFrom['block'];
        $increase = $block;
        $bitsTo = $configTo['bits'];
        $bitmask = $configTo['bitmask'];
        $index = 0;

        $bindingEncode = &$this->{$configTo['bindingEncode']};
        $bindingOrd = array_flip(InitArray::ord100()); //Restricted within the allowed ords only (static)

        for ($i = 0; $i < $len; $i++) {

            $ord = $bindingOrd[$symbol[$i]] ?? null;
            if($ord === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function precompute2Compute() (char: ' .  \mb_substr($symbol, 0, Prefix::byteLength($symbol[$pos])) . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo(
                        substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                    );

                return null; 
            }    

            $buffer = ($buffer << 8) | $ord;
            $bitsInBuffer += 8;

            while ($bitsInBuffer >= $bitsTo) {
                $bitsInBuffer -= $bitsTo;
                $nb = ($buffer >> $bitsInBuffer) & $bitmask;
                $encoded[$index++] = $bindingEncode[$nb];
            }

            if($i >= $increase) {
                $converted .= implode("", $encoded);
                $increase += $block; 
                $index = 0;
                $encoded = [];
            }
        }

        if ($bitsInBuffer > 0) {
            $saltBits = $bitsTo - $bitsInBuffer;
            $bufferPart = Calculation::getLastBits($buffer, $bitsInBuffer) << $saltBits;
            $nb = $bufferPart | (ord($bindingEncode[0]) & (1 << $saltBits) - 1);
            $encoded[] = $bindingEncode[$nb];     
        }

        if(count($encoded) > 0) $converted .= implode("", $encoded);

        return $converted;
    }

    protected function bitshiftPrecompute100(string $symbol, array $configFrom, array $configTo) : ?string { 
        
        $len = strlen($symbol);
        $allowedStr = &$this->{$configFrom['bindingStr']};
        $quickCheck = $this->checkBase($symbol, $len, $allowedStr, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in bitshiftPrecompute100() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
     
            return null; 
        }

        $bindingEncode = InitArray::ord100();
        $bindingDecode = &$this->{$configFrom['bindingDecode']};
        
        $bitsFrom = $configFrom['bits'];
        $block = $configFrom['block']; 
        $increase = $block;

        //calc
        $converted = '';
        $buffer = 0;
        $bitsInBuffer = 0;
        $digs = '';
        $decoded = [];
        $index = -1;

        for ($i = 0; $i < $len; $i++) {
            $digs = $bindingDecode[$symbol[$i]] ?? null;
            if($digs === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function bitshiftPrecompute100() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " position: " . $pos+1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo(
                        substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                    );

                return null;
            }

            $buffer = ($buffer << $bitsFrom) | $digs;
            $bitsInBuffer += $bitsFrom;

            while ($bitsInBuffer >= 8) {
                $bitsInBuffer -= 8;
                $nb = ($buffer >> $bitsInBuffer) & 0xFF;
                   
                $decoded[++$index] = $bindingEncode[$nb] ?? null;
                if($decoded[$index] === null) {
                    $pos = $i;
                    $this->setErrorStatus(false) 
                        ->setErrorMessage('Bufferprocess found invalid input in function bitshiftPrecompute100() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " positie: " . ($pos) + 1 . ')')
                        ->setCustomerMessage('Invalid input in string')
                        ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

                    return null;
                } 
            }

            if($i >= $increase) {
                $converted .= implode("", $decoded);
                $increase += $block; 
                $index = -1;
                $decoded = [];
            }
        }

        if(count($decoded) > 0) $converted .= implode("", $decoded);

        return $converted;
    }

    protected function precomputeCompute100(string $symbol, array $configFrom, array $configTo) : ?string { 
        
        $len = strlen($symbol);

        //Big Endian will not check the special role of base100, so full check is required
        //$fullcheck = $this->fullCheck($symbol, $this->{$configFrom['bindingStr']}, $len);
        $fullcheck = $this->fullCheck($symbol, InitString::_base100(), $len);
        if(!$fullcheck) {
            $this->setErrorStatus(false) 
                ->setErrorMessage('Bufferprocess found invalid input in function precompute2Compute() (char:')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
    
            return null; 
        }

        return $this->endianToCompute(
            $this->getEndianChunk($symbol, $len), 
            $configTo
        );
    }

    protected function computePrecompute100(string $symbol, array $configFrom, array $configTo) : ?string { 
        
        $bigEndian = $this->compute2Endian($symbol, $configFrom, $configTo);
        if($bigEndian === null) {
            return $bigEndian;
        }

        $len = strlen($bigEndian);
        $converted = $this->getText($bigEndian, $len);
    
        //Big Endian will not check the special role of base100, so full check is required here
        $fullcheck = $this->fullCheck($converted, InitString::_base100(), strlen($converted));
        if(!$fullcheck) {
            $this->setErrorStatus(false) 
                ->setErrorMessage('Bufferprocess found invalid input in function computePrecompute100()')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
            );
    
            return null; 
        }       
    
        return $converted;  
    }

    protected function computePrecompute10(string $symbol, array $configFrom, array $configTo) : ?string {

        $chunkEndian = $this->compute2Endian($symbol, $configFrom, $configTo);
        if($chunkEndian === null) {
            return $chunkEndian;
        }

        $len = strlen($chunkEndian);
        $converted = $this->getText($chunkEndian, $len);

        $this->lazyLoading_baseConfig100();
        return $this->precompute100Precompute10($converted, $this->_baseConfig100, $configTo);
    }

    protected function precomputeCompute10(string $digitPairs, array $configFrom, array $configTo) : ?string { 

        $this->lazyLoading_baseConfig100();
        $symbol = $this->precompute10Precompute100($digitPairs, $configFrom, $this->_baseConfig100);
        if($symbol === null) {
            return $symbol;
        }

        $digits = $this->getEndianChunk($symbol, strlen($symbol));        

        //Convertion
        $chunk = $configTo['exponentiation']['chunk'];
        $len = strlen($digits);
        $left = $len % $chunk;
        $left = $left === 0 ? $chunk : $left;
        $block = (int)floor(154 / $chunk) * $chunk;
        $bindingEncode = &$this->{$configTo['bindingEncode']};
        $initBase = $configTo['exponentiation']['init'];

        $converted = '';
        $buffer = [];
        $rounds = (int)ceil(($len-$left) / $block);
        $pointer = $left;
        $index = 0;
        for ($i=0; $i < $rounds; $i++) {

            $digs = substr($digits, $pointer, $block);  

            $splitDigits = \str_split($digs, $chunk);
            $splitLen = \count($splitDigits);

            for ($a = 0; $a < $splitLen; $a++) {
                $calc = (int)$splitDigits[$a];
                foreach ($initBase as $exponentiation) {
                    $nb = (int)($calc/$exponentiation);
                    $buffer[$index++] = $bindingEncode[$nb];
                    $calc = $calc % $exponentiation;
                } 
            }

            $converted .= implode('', $buffer);
            $index = 0;
            $buffer = [];
            $pointer += $block; 
        }

        $number = (int)substr($digits, 0, $left);
        $calc = $number;
        $concat = "";
        foreach ($initBase as $exponentiation) {
            $nb = (int)($calc/$exponentiation);
            $concat .= $bindingEncode[$nb];
            $calc = $calc % $exponentiation;
        } 

        //Grab random zero-symbols
        $symbolZeros = 0;
        while (true) {
            if(substr($concat, $symbolZeros, 1) !== $bindingEncode[0]) {
                break;
            }       
            $symbolZeros++;
        }

        //Ouput format
        if($symbolZeros > 0) {
           return substr($concat, $symbolZeros, null) . $converted; 
        } 
        else {
           return $concat . $converted; 
        }  
    }    

    protected function precomputeCompute256(string $text, array $configFrom, array $configTo) : ?string { 
        
        $base100 = $this->precompute256Precompute100($text, $configFrom, ['base' => 100]);
        return $this->precomputeCompute100($base100, ['bindingEncode' => "_base100"], $configTo);
    }

    protected function computePrecompute256(string $symbol, array $configFrom, array $configTo) : ?string { 

        $bigEndian = $this->compute2Endian($symbol, $configFrom, ['bindingEncode' => "_base100"]);

        if($bigEndian === null) {
            return $bigEndian; 
        }

        $len = strlen($bigEndian);
        $converted = $this->getText($bigEndian, $len);

        return $this->base100Tobase256($converted, $configFrom, $configTo);
    }

    protected function precomputeBitshift256(string $text, array $configFrom, array $configTo) : ?string { 

        $base100 = $this->precompute256Precompute100($text, $configFrom, ['base' => 100]);
        
        if($base100 === null) {
            return $base100;
        }

        $len = \strlen($base100);

        $buffer = 0;
        $bitsInBuffer = 0;
        $encoded = [];
        $converted = '';

        //Variable settings
        $block = $configFrom['block'];
        $increase = $block;
        $bitsTo = $configTo['bits'];
        $bitmask = $configTo['bitmask'];
        $index = 0;

        $bindingEncode = &$this->{$configTo['bindingEncode']};

        for ($i = 0; $i < $len; $i++) {

            $buffer = ($buffer << 8) | ord($base100[$i]);
            $bitsInBuffer += 8;

            while ($bitsInBuffer >= $bitsTo) {
                $bitsInBuffer -= $bitsTo;
                $nb = ($buffer >> $bitsInBuffer) & $bitmask;
                $encoded[$index++] = $bindingEncode[$nb];
            }

            if($i >= $increase) {
                $converted .= implode("", $encoded);
                $increase += $block; 
                $index = 0;
                $encoded = [];
            }
        }

        if ($bitsInBuffer > 0) {
            $saltBits = $bitsTo - $bitsInBuffer;
            $bufferPart = Calculation::getLastBits($buffer, $bitsInBuffer) << $saltBits;
            $nb = $bufferPart | (ord($bindingEncode[0]) & (1 << $saltBits) - 1);
            $encoded[] = $bindingEncode[$nb];          
        }

        if(count($encoded) > 0) $converted .= implode("", $encoded);
        
        return $converted;
    }

    protected function bitshiftPrecompute256(string $symbol, array $configFrom, array $configTo) : ?string {

        //$this->lazyLoading_baseConfig100();
        /***  aangepast */
        $base100 = $this->bitshiftPrecompute100($symbol, $configFrom, ['base' => 100, 'bindingEncode' => '_base100']);
        if($base100 === null) {
            return $base100;
        }
            
        return $this->base100Tobase256($base100, $configFrom, $configTo);
    } 

    protected function precompute100Precompute100(string $symbol, array $configFrom, array $configTo) : ?string { 
        if(empty($symbol)) {
            $this->setErrorStatus(false) 
                ->setErrorMessage('Quickcheck found empty input in function precompute100Precompute100()')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

            return null;
        }

        if(!$this->fullCheck($symbol, InitString::_base100(), strlen($symbol))) {
            $this->setErrorStatus(false) 
                ->setErrorMessage('Fullchecker found invalid input in function precompute100Precompute100()')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
    
            return null;            
        }

        return $symbol;
    }

    protected function precompute10Precompute10(string $digitPairs, array $configFrom, array $configTo) : ?string {
        $fullcheck = \ctype_digit($digitPairs);
        if($fullcheck === false) {
            $this->setErrorStatus(false) 
                ->setErrorMessage('Fullchecker found invalid input in function precompute10Precompute10()')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

            return null;
        }

        return $digitPairs;
    }

    protected function precompute256Precompute256(string $text, array $configFrom, array $configTo) : ?string { 
        if(empty($text)) {
            $this->setErrorStatus(false) 
                ->setErrorMessage('Quickcheck found empty input in function precompute256Precompute256()')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

            return null;
        }
        return $text;
    }

    protected function precomputeBitshift10(string $digitPairs, array $configFrom, array $configTo) : ?string {

        //Sampling-quickcheck
        $len = \strlen($digitPairs);
        $allowedStr = &$this->{$configFrom['bindingStr']};
        $quickCheck = $this->checkBase($digitPairs, $len, $allowedStr, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in precompute10Precompute100() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
                
            return null;   
        }        
        
        if($len % 2 === 1) {
            throw new InputException("The first argument must have an even digit length");
        }  

        //Prepare Dimensional array for checking input digits
        if($this->_base100Dimensional === null) {
            $this->_base100Dimensional = InitArray::dimensionalIndex100();
            for ($i=0; $i < 100; $i++) {
                $index = sprintf("%02d", $i);
                $this->_base100Dimensional[$index[0]][$index[1]] = $this->_base100[$i];
            }
            MemeZero::overwriteArray($this->_base100);
        }

        $buffer = 0;
        $bitsInBuffer = 0;
        $encoded = [];
        $converted = '';      

        //Variable settings
        $block = $configFrom['block'];
        $increase = $block;
        $bitsTo = $configTo['bits'];
        $bitmask = $configTo['bitmask'];
        $index = 0;

        $bindingEncode = &$this->{$configTo['bindingEncode']};

        for ($i=0; $i < $len-1; $i += 2) { 

            $char = $this->_base100Dimensional[$digitPairs[$i]][$digitPairs[$i+1]] ?? null;
            if($char === null) {
                $pos = $i;
                $this->setErrorStatus(false)
                    ->setErrorMessage('Bufferprocess found invalid input in function precompute10Precompute100() (char: ' 
                        . \mb_substr($digitPairs, $pos, Prefix::byteLength($digitPairs[$pos])) 
                        . \mb_substr($digitPairs, $pos+1, Prefix::byteLength($digitPairs[$pos+1])) 
                        . " positie: " . ($pos) + 1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");
                    
                return null;
            }  

            $buffer = ($buffer << 8) | ord($char);
            $bitsInBuffer += 8;

            while ($bitsInBuffer >= $bitsTo) {
                $bitsInBuffer -= $bitsTo;
                $nb = ($buffer >> $bitsInBuffer) & $bitmask;
                $encoded[$index++] = $bindingEncode[$nb];
            }

            if($i >= $increase) {
                $converted .= implode("", $encoded);
                $increase += $block; 
                $index = 0;
                $encoded = [];
            }
        } 
       
        if ($bitsInBuffer > 0) {
            $saltBits = $bitsTo - $bitsInBuffer;
            $bufferPart = Calculation::getLastBits($buffer, $bitsInBuffer) << $saltBits;
            $nb = $bufferPart | (ord($bindingEncode[0]) & (1 << $saltBits) - 1);
            $encoded[] = $bindingEncode[$nb];
        }

        if(count($encoded) > 0) $converted .= implode("", $encoded);    

        return $converted;
    }
    
    protected function bitshiftPrecompute10(string $symbol, array $configFrom, array $configTo) : ?string { 
        
        $len = strlen($symbol);
        $allowedStr = &$this->{$configFrom['bindingStr']};
        $quickCheck = $this->checkBase($symbol, $len, $allowedStr, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in bitshiftPrecompute10() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
     
            return null; 
        }

        $bindingEncode = &$this->_base100Reverse;
        $bindingDecode = &$this->{$configFrom['bindingDecode']};    
        
        $bits = $configFrom['bits'];
        $block = $configFrom['block']; 
        $increase = $block;

        //calc
        $converted = '';
        $buffer = 0;
        $bitsInBuffer = 0;
        $digs = '';
        $decoded = [];
        $index = -1;
        for ($i = 0; $i < $len; $i++) {

            $digs = $bindingDecode[$symbol[$i]] ?? null;
            if($digs === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function bitshiftPrecompute10() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " position: " . $pos+1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo(
                        substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                    );

                return null;
            }

            $buffer = ($buffer << $bits) | $digs;
            $bitsInBuffer += $bits;

            while ($bitsInBuffer >= 8) {
                $bitsInBuffer -= 8;
                $nb = ($buffer >> $bitsInBuffer) & 0xFF;       
                $decoded[++$index] = $bindingEncode[chr($nb)] ?? null;

                if($decoded[$index] === null) {
                    $pos = $i;
                    $this->setErrorStatus(false) 
                        ->setErrorMessage('Bufferprocess found invalid input in function bitshiftPrecompute10() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " positie: " . ($pos) + 1 . ')')
                        ->setCustomerMessage('Invalid input in string')
                        ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

                    return null;
                } 
            }

            if($i >= $increase) {
                $converted .= implode("", $decoded);
                $increase += $block; 
                $index = -1;
                $decoded = [];
            }
        }

        if(count($decoded) > 0) $converted .= implode("", $decoded);

        return $converted;
    }

    //Example: base64 to base32
    protected function bitshift(string $symbol, array $configFrom, array $configTo): ?string { 
        
        $len = strlen($symbol);
        $allowedStr = &$this->{$configFrom['bindingStr']};
        $quickCheck = $this->checkBase($symbol, $len, $allowedStr, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in bitshiftPrecompute10() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
     
            return null; 
        } 

        //Binding Decode 
        $bindingDecode = &$this->{$configFrom['bindingDecode']};    
        $bitsFrom = $configFrom['bits'];
        $block = $configFrom['block'];

        //Binding Encode
        $bindingEncode = &$this->{$configTo['bindingEncode']};
        $bitsTo = $configTo['bits'];
        $bitmaskTo = $configTo['bitmask'];

        //calc
        $increase = $block; 
        $converted = '';
        $buffer = 0;
        $bitsInBuffer = 0;
        $digs = '';
        $decoded = [];
        $index = -1;
        for ($i = 0; $i < $len-1; $i++) {

            $digs = $bindingDecode[$symbol[$i]] ?? null;
            if($digs === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function bitshiftPrecompute10() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " position: " . $pos+1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo(
                        substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                    );

                return null;
            }

            $buffer = ($buffer << $bitsFrom) | $digs;
            $bitsInBuffer += $bitsFrom;

            while ($bitsInBuffer >= $bitsTo) {
                $bitsInBuffer -= $bitsTo;
                $nb = ($buffer >> $bitsInBuffer) & $bitmaskTo;       
                $decoded[++$index] = $bindingEncode[$nb]; 
            }

            if($i >= $increase) {
                $converted .= implode("", $decoded);
                $increase += $block; 
                $index = -1;
                $decoded = [];
            }
        }
        
        //Total length in bits of encoded string
        $totalBits = $len * $bitsFrom;

        //Original length in bits when decoded (txt)
        $originalBits = floor($len * $bitsFrom / 8) * 8;

        //Calculate to original length without decoded
        $requiredBits = $originalBits - ($totalBits-$bitsFrom-$bitsInBuffer);

        //Step 1: fill the buffer with BitsInBuffer left
        if($bitsInBuffer > 0) {
            $buffer = Calculation::getLastBits($buffer, $bitsInBuffer);
        }
        else {
            $buffer = 0;
        }

        //How many bits are left in the last symbol + grab the last symbol
        $firstBits = $requiredBits - $bitsInBuffer;
        if($firstBits > 0) {
            $ord = $bindingDecode[$symbol[$len - 1]];
            $buffer = ($buffer << $firstBits) | Calculation::getFirstBits($ord, $firstBits, $bitsFrom);       
        }

        //Calculate salt bits
        $newSalt = (ceil($originalBits / $bitsTo) * $bitsTo) - $originalBits;        
        if($newSalt > 0) {
            $buffer = $buffer << $newSalt;
            $buffer |= ord($bindingEncode[0]) & (1 << $newSalt) - 1;
        }

        //Grab the last symbols
        $bitsInBuffer = $requiredBits+$newSalt;
        while ($bitsInBuffer > 0) {
            $bitsInBuffer -= $bitsTo;
            $nb = ($buffer >> $bitsInBuffer) & $bitmaskTo;
            $decoded[] = $bindingEncode[$nb]; 
        }

        if(count($decoded) > 0) $converted .= implode("", $decoded);

        return $converted;
    }

    protected function computeBitshift(string $symbol, array $configFrom, array $configTo) : ?string { 

        $bigEndian = $this->compute2Endian($symbol, $configFrom, $configTo);
        if($bigEndian === null) {
            return $bigEndian;
        }

        $txt = $this->getText($bigEndian, \strlen($bigEndian));
        $len = \strlen($txt);

        $buffer = 0;
        $bitsInBuffer = 0;
        $encoded = [];
        $converted = '';      

        //Variable settings
        $block = 64;
        $increase = $block;
        $bitsTo = $configTo['bits'];
        $bitmask = $configTo['bitmask'];
        $index = 0;

        $bindingEncode = &$this->{$configTo['bindingEncode']};

        for ($i = 0; $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($txt[$i]);
            $bitsInBuffer += 8;

            while ($bitsInBuffer >= $bitsTo) {
                $bitsInBuffer -= $bitsTo;
                $nb = ($buffer >> $bitsInBuffer) & $bitmask;
                $encoded[$index++] = $bindingEncode[$nb];
            }

            if($i >= $increase) {
                $converted .= implode("", $encoded);
                $increase += $block; 
                $index = 0;
                $encoded = [];
            }
        }

        if ($bitsInBuffer > 0) {
            $saltBits = $bitsTo - $bitsInBuffer;
            $bufferPart = Calculation::getLastBits($buffer, $bitsInBuffer) << $saltBits;
            $nb = $bufferPart | (ord($bindingEncode[0]) & (1 << $saltBits) - 1);
            $encoded[] = $bindingEncode[$nb];     
        }

        if(count($encoded) > 0) $converted .= implode("", $encoded);

        return $converted;    
    }

    protected function bitshiftCompute(string $symbol, array $configFrom, array $configTo) : ?string { 
        
        $len = strlen($symbol);
        $allowedStr = &$this->{$configFrom['bindingStr']};
        $quickCheck = $this->checkBase($symbol, $len, $allowedStr, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in bitshiftPrecompute100() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
     
            return null; 
        }

        $bindingDecode = &$this->{$configFrom['bindingDecode']};    
        
        $bitsFrom = $configFrom['bits'];
        $block = $configFrom['block'];
        $increase = $block;

        //calc
        $converted = '';
        $buffer = 0;
        $bitsInBuffer = 0;
        $digs = '';
        $decoded = [];
        $index = -1;

        for ($i = 0; $i < $len; $i++) {
            $digs = $bindingDecode[$symbol[$i]] ?? null;
            if($digs === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function bitshiftPrecompute100() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " position: " . $pos+1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo(
                        substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                    );

                return null;
            }

            $buffer = ($buffer << $bitsFrom) | $digs;
            $bitsInBuffer += $bitsFrom;

            while ($bitsInBuffer >= 8) {
                $bitsInBuffer -= 8;
                $nb = ($buffer >> $bitsInBuffer) & 0xFF;

                $decoded[++$index] = chr($nb);
            }

            if($i >= $increase) {
                $converted .= implode("", $decoded);
                $increase += $block; 
                $index = -1;
                $decoded = [];
            }
        }

        if(count($decoded) > 0) $converted .= implode("", $decoded);
        
        $len = strlen($converted);
        return $this->endianToCompute(
            $this->getEndianChunk($converted, $len), 
            $configTo
        );
    }

    protected function compute(string $symbol, array $configFrom, array $configTo) : ?string { 
        $bigEndian = $this->compute2Endian($symbol, $configFrom, $configTo);
        return $this->endianToCompute($bigEndian, $configTo);
    }

    protected function chunksize() : array {
        if($this->longEndianChunk) return [169, 407];
        return [22, 53];
    }

    //Set given alphabet with generic binding
    protected function setAlphabet(array $singleBytes, int $baseLength, string $configName) : void { 

        $config = $this->{$configName};
        if(empty($this->{$config['bindingStr']})) {

            $length = count($singleBytes);                 
            if($length !== $baseLength) {
                $singleBytes = array_slice($singleBytes, 0, $baseLength);
            }

            //Initialization
            $this->{$config['bindingStr']} = implode('', $singleBytes);
            $this->{$config['bindingEncode']} = $singleBytes;
            $this->{$config['bindingDecode']} = [];

            //Binding with local variable by reference
            $bindingEncode = &$this->{$config['bindingEncode']};
            $bindingDecode = &$this->{$config['bindingDecode']};

            if($config['checksum']) {
                foreach ($bindingEncode as $index => $value) {
                    $bindingDecode[$value] = sprintf("%02d", $index);
                }
            }
            else {
                $bindingDecode = array_flip($singleBytes);
            }
        }
    }

    final protected function clearBaseConvert() : void {
        $props = [
            "_base256Int", "_base256IntReverse", "_base100Dimensional"
        ];
        
        if($this->_baseConfig10 !== null) {
            foreach ($props as $value) {
                $prop = &$this->{$value};
                if($prop !== null) {
                    MemeZero::overwriteArray($prop);
                    unset($prop);
                }
            }
        }

        $configs = [
            $this->_baseConfig32, $this->_baseConfig58, $this->_baseConfig62, $this->_baseConfig64, 
            $this->_baseConfig100, $this->_customConfig256From, $this->_customConfig256To
        ];

        foreach ($configs as $config) {
            if($config !== null) {
                $str = $this->{$config['bindingStr']};
                if(isset($str) && $str !== null) {
                    $encode = &$this->{$config['bindingEncode']};
                    $decode = &$this->{$config['bindingDecode']};   
                    MemeZero::overwriteString($str);
                    MemeZero::overwriteArray($encode);
                    MemeZero::overwriteArray($decode);
                    unset($str, $encode, $decode);
                }
            }
        }
    }

    //Help logic-business methods
    private function getText(string $bigEndianChunk, int $len) : string {

        if(empty($bigEndianChunk)) return "";

        $text = "";
        $chunk = $this->chunksize();
        $byteBlock = $chunk[0];
        $digBlock = $chunk[1];
        $left = $len % $digBlock;
        $left = $left === 0 ? $digBlock : $left;
        $rounds = (int)floor(($len-$left) / $digBlock);

        if($this->useGMP()) {
            //Use GMP if aivalaible othwise  PHP
            if($left > 0) {
                $text .= \gmp_export(
                    \gmp_init(substr($bigEndianChunk, 0, $left), 10)
                );

                //Is not a uniform Big Endian Chunk
                if(strlen($text) > $byteBlock) throw new InputException("Input digits (via GMP) are non-uniform for this context");
            }
            $pointer = $left;
            for ($i=0; $i < $rounds; $i++) { 
                $converted = \str_pad(
                    \gmp_export(\gmp_init(substr($bigEndianChunk, $pointer, $digBlock), 10)), $byteBlock, "\0", STR_PAD_LEFT
                );

                if(strlen($converted) > $byteBlock) throw new InputException("Input digits (via GMP) are non-uniform for this context");         

                $text .= $converted;
                $pointer += $digBlock;
            }
        }
        else {
            //Use PHP BC (same genarated decimals as GMP)
            if($left > 0) {
                $text .= Calculation::decToBytes(substr($bigEndianChunk, 0, $left));

                //Is not a uniform Big Endian Chunk
                if(strlen($text) > $byteBlock) 
                    throw new InputException("Input digits (via BC) are non-uniform for this context");
            }
            $pointer = $left;
            for ($i=0; $i < $rounds; $i++) { 
                $converted = \str_pad(
                    Calculation::decToBytes(substr($bigEndianChunk, $pointer, $digBlock)), $byteBlock, "\0", STR_PAD_LEFT
                );

                if(strlen($converted) > $byteBlock) 
                    throw new InputException("Input digits (via BC) are non-uniform for this context");

                $text .= $converted;
                $pointer += $digBlock;
            }
        }

        return $text;
    }

    private function getEndianChunk(string $txt, int $len) : string {
        //Calculate Bytes to Decimal with dynamic chunk endian-number
        if(empty($txt)) return "";

        $chunk = $this->chunksize();
        $byteBlock = $chunk[0];            
        $digBlock = $chunk[1];
        $left = $len % $byteBlock;  
        $left = $left === 0 ? $byteBlock : $left;
        $rounds = (int)floor(($len-$left) / $byteBlock);

        $digits = "";
        //Use GMP if aivalaible othwise use PHP
        if($this->useGMP()) {
            if($left > 0) {
                $digits .= \gmp_strval(\gmp_import(substr($txt, 0, $left)));
            }
            $pointer = $left;
            for ($i=0; $i < $rounds; $i++) { 
                $digits .= \str_pad(
                    \gmp_strval(\gmp_import(substr($txt, $pointer, $byteBlock))), $digBlock, '0', STR_PAD_LEFT
                );

                $pointer += $byteBlock;
            }
        }
        else {
            //Use PHP only (same genarated decimals as GMP)
            if($left > 0) {
                $digits .= Calculation::bytesToDec(substr($txt, 0, $left));
            }
            $pointer = $left;
            for ($i=0; $i < $rounds; $i++) { 
                $digits .= \str_pad(
                    Calculation::bytesToDec(substr($txt, $pointer, $byteBlock)), $digBlock, '0', STR_PAD_LEFT
                );
               $pointer += $byteBlock;
            }
        }

        return $digits;
    }

    private function endianToCompute(string $bigEndian, array $configTo) : ?string {

        //Convertion
        $chunk = $configTo['exponentiation']['chunk'];
        $len = strlen($bigEndian);
        $left = $len % $chunk;
        $left = $left === 0 ? $chunk : $left;
        $block = (int)floor(154 / $chunk) * $chunk;
        $bindingEncode = &$this->{$configTo['bindingEncode']};
        $initBase = $configTo['exponentiation']['init'];

        $converted = '';
        $buffer = [];
        $rounds = (int)ceil(($len-$left) / $block);
        $pointer = $left;
        $index = 0;
        for ($i=0; $i < $rounds; $i++) {

            $digs = substr($bigEndian, $pointer, $block);  

            $splitDigits = \str_split($digs, $chunk);
            $splitLen = \count($splitDigits);

            for ($a = 0; $a < $splitLen; $a++) {
                $calc = (int)$splitDigits[$a];
                foreach ($initBase as $exponentiation) {
                    $nb = (int)($calc/$exponentiation);
                    $buffer[$index++] = $bindingEncode[$nb];
                    $calc = $calc % $exponentiation;
                } 
            }

            $converted .= implode('', $buffer);
            $index = 0;
            $buffer = [];
            $pointer += $block; 
        }

        $number = (int)substr($bigEndian, 0, $left);
        $calc = $number;
        $concat = "";
        foreach ($initBase as $exponentiation) {
            $nb = (int)($calc/$exponentiation);
            $concat .= $bindingEncode[$nb];
            $calc = $calc % $exponentiation;
        } 

        //Grab random zero-symbols
        $symbolZeros = 0;
        while (true) {
            if(substr($concat, $symbolZeros, 1) !== $bindingEncode[0]) {
                break;
            }       
            $symbolZeros++;
        }

        //Ouput format
        if($symbolZeros > 0) {
           return substr($concat, $symbolZeros, null) . $converted; 
        } 
        else {
           return $concat . $converted; 
        }  
    }

    private function compute2Endian(string $symbol, array $configFrom, array $configTo) : ?string {
    
        $len = strlen($symbol);
        $allowedStr = &$this->{$configFrom['bindingStr']};
        $quickCheck = $this->checkBase($symbol, $len, $allowedStr, $configFrom);
        if(!$quickCheck){
            $this->setErrorStatus(false)
                ->setErrorMessage('Quickcheck found invalid input in computePrecompute100() function')
                ->setCustomerMessage('Invalid input in string')
                ->setInfo(
                    substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 1, null)
                );
     
            return null;
        }

        $countSymbols = $configFrom['exponentiation']['symbol'];
        $chunk = $configFrom['exponentiation']['chunk'];
        $blockLength = (int)floor(154 / $chunk) * $countSymbols;
        $left = $len % $countSymbols;
        $left = $left === 0 ? $countSymbols : $left;

        $bindingDecode = &$this->{$configFrom['bindingDecode']};
        $initBase = $configFrom['exponentiation']['init'];

        //First handle left
        $byte = substr($symbol, 0, $left);
        $digs = 0;
        $baseFrom = $configFrom['base'];

        $digits = '';
        $exponentiation = $initBase[count($initBase)-1];
        for ($a = strlen($byte)-1; $a >= 0; $a--) {
            $mul = (int)($bindingDecode[$byte[$a]] * $exponentiation);
            $digs += $mul;
            $exponentiation *= $baseFrom;
        }

        $digits .= $digs;

        //Handle bulk
        $rounds = (int)ceil(($len-$left) / $blockLength);
        $pointer = $left;
        for ($i=0; $i < $rounds; $i++) {

            $blockSymbol = \substr($symbol, $pointer, $blockLength);

            $splitSymbol = str_split($blockSymbol, $countSymbols);
            $countSplitSymbol = count($splitSymbol);

            $number = [];
            for ($a=0; $a < $countSplitSymbol; $a++) { 
                $bytes = $splitSymbol[$a];
                $digs = 0;
                for ($x = 0; $x < $countSymbols; $x++) {
                    $byte = $bindingDecode[$bytes[$x]] ?? null;
                    if($byte === null) {
                        $pos = $x;
                        $this->setErrorStatus(false) 
                            ->setErrorMessage('Bufferprocess found invalid input in function precompute2Compute() (char: ' .  \mb_substr($bytes, $pos, Prefix::byteLength($bytes[$pos])) . ')')
                            ->setCustomerMessage('Invalid input in string')
                            ->setInfo(
                                substr($configFrom['bindingEncode'], 1, null) . " to " . substr($configTo['bindingEncode'], 
                                1, 
                                null
                            )
                        );
        
                        return null;
                    }

                    $digs += (int)($byte * $initBase[$x]);
                }

                $number[] = \str_pad((string)$digs, $chunk,"0", STR_PAD_LEFT);  
            }

            $digits .= implode("", $number);
            $pointer += $blockLength;
        }

        return $digits;
    }

    private function base100Tobase256(string $symbol, array $configFrom, array $configTo) : ?string {

        $len = strlen($symbol);

        //Grab the symbolzeros in the string
        $symbolZeros = 0;
        $target = $this->{$configTo['bindingEncode']}[0];
        while ($symbolZeros < $len) {
            $char = substr($symbol, $symbolZeros, 1);
            if ($char !== $target) break;
            $symbolZeros++;
        }

        $base100 = $symbolZeros > 0 ? substr($symbol, $symbolZeros, null) : $symbol; 

        //Calculations    
        $len -= $symbolZeros;  
        $initBase100 = InitArray::initBase100();
        
        //Calculation
        $left = $len % 7;
        $left = $left === 0 ? 7 : $left;
        $bindingDecode = &$this->_base256Reverse;

        //Covertion
        $converted = "";
        $buffer = [];
        $block = 77;
        $increase = $block;
        $index = -1;
        for ($i=$left; $i < $len; $i++) { 
            
            $buffer[++$index] = $bindingDecode[$base100[$i]] ?? null;
            if($buffer[$index] === null) {
                $pos = $i;
                $this->setErrorStatus(false) 
                    ->setErrorMessage('Bufferprocess found invalid input in function precompute100Precompute256() (char: ' .  \mb_substr($symbol, $pos, Prefix::byteLength($symbol[$pos])) . " positie: " . ($pos) + 1 . ')')
                    ->setCustomerMessage('Invalid input in string')
                    ->setInfo("from({$configFrom['base']}) > to({$configTo['base']}) > convert(input)");

                return null;
            }
            
            if($i >= $increase) {
                $converted .= implode('', $buffer);
                $buffer = [];
                $increase += $block;
                $index = -1;
            }             
        }

        if(count($buffer) > 0) $converted .= implode('', $buffer);

        $front = str_split(\substr($base100, 0, $left));
        $digs = 0;
        $exponentiation = $initBase100[6];
        for ($a = count($front)-1; $a >= 0; $a--) {
            $mul = (int)($bindingDecode[$front[$a]] * $exponentiation);
            $digs += $mul;
            $exponentiation *= 100;
        }
    
        //Convertion
        $text = '';
        if($symbolZeros > 0) {
            $text .= str_repeat("\0", $symbolZeros);
        }  

        $digits = $digs . $converted;
        $len = strlen($digits);
        $text .= $this->getText($digits, $len);
        return $text;
    }

    //Public methods

    public function useGMP() : bool  {
        return !empty($this->_gmp);
    }
    
}