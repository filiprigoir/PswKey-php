<?php
declare(strict_types=1);

namespace PswKey\Service;

use Exception;
use PswKey\Core\Modifiers\ShuffleProfile;
use PswKey\Core\ShuffleChars;
use PswKey\ErrorMessage\ErrorMessage;
use PswKey\ErrorMessage\InternalMessage;
use PswKey\Exception\BaseException;
use PswKey\Exception\ConfigurationException;
use PswKey\Interface\ConvertEngineInterface;
use PswKey\Interface\ConvertBaseInterface;
use PswKey\Util\Base\CheckBase;
use PswKey\Util\Base\InitArray;
use PswKey\Util\Base\Precompute;
use PswKey\Util\Mapping\Merge;
use PswKey\Util\Secure\MemeZero;

/**
 * Encodes and decodes single-byte input as deterministic context-bound transport keys.
 * For multibyte strings, explicitly use Transcode::getISO(input) and Transcode::getUTF(output).
 */
class PswKey extends ShuffleChars implements ConvertBaseInterface, ConvertEngineInterface {

    public function __construct(KeyStream $keyStream) {
        parent::__construct($keyStream);
    }

    /**** Public Methods ****/

    public function convert(string $mix) : ?string {

        //Clear & start new Convertion
        $this->resetValidator();

        //Binding properties
        $configFrom = $this->{$this->_from} ?? null;
        $configTo = $this->{$this->_to} ?? null;

        if(!$configFrom || !$configTo) {
            throw new ConfigurationException(InternalMessage::INCOMPLETE_METHOD_CHAIN);
        }

        //Binding methods
        $func = match ($configFrom['process'] . $configTo['process']) {
            "precomputecompute" => "precomputeCompute{$configFrom['base']}",
            "computeprecompute" => "computePrecompute{$configTo['base']}",
            "precomputebitshift" => "precomputeBitshift{$configFrom['base']}", 
            "bitshiftprecompute" => "bitshiftPrecompute{$configTo['base']}", 
            "computebitshift" => "computeBitshift",
            "bitshiftcompute" => "bitshiftCompute",
            "precomputeprecompute" => "precompute{$configFrom['base']}precompute{$configTo['base']}",
            "computecompute" => "compute",
            "bitshiftbitshift" => "bitshift",
            default => throw new BaseException(InternalMessage::CONFIG_ERROR_DEFAULT)
        };

        try {
            return $this->$func($mix, $configFrom, $configTo); 
        }
        catch(Exception $e) {
            $messages = ErrorMessage::create($e->getMessage());
            $this->setErrorStatus(false)
                ->setInternalMessage($messages['internal'])
                ->setClientMessage($messages['client']);
        
            return null;
        }
    }

    public function from(int $base) : self {

        if(!CheckBase::defaultShuffle($base)) {
            throw new BaseException(
                Merge::string(InternalMessage::RADIX_UNSUPPORTED, [
                    '%base%', "Base{$base}"
                ])
            );
        } 

        //binding baseFrom
        $this->_from = "_baseConfig" . $base;
        $this->{"lazyLoading" . $this->_from}();

        if($base === 256) {
            $this->setAlphabet(InitArray::_base100(), 100, $this->_from);
        }
        elseif($base !== 100) {

            $func = $this->{$this->_from}['bindingEncode'];

            if($this->enabledFFI()) {
                $this->shuffleFFI(InitArray::$func(), $base, $this->_from, false); 
            }
            else {
                $this->shufflePHP(InitArray::$func(), $base, $this->_from, false);
            }            
        }

        return $this;
    }

    public function to(int $base) : self {

        if(!CheckBase::defaultShuffle($base)) {
            throw new BaseException(
                Merge::string(InternalMessage::RADIX_UNSUPPORTED, [
                    '%base%', "Base{$base}"
                ])
            );       
        }

        //Binding baseTo
        $this->_to = "_baseConfig" . $base;
        $this->{"lazyLoading" . $this->_to}();        

        if($base === 256) {
            $this->setAlphabet(InitArray::_base100(), 100, $this->_to);
        }
        elseif($base !== 100) {

            $func = $this->{$this->_to}['bindingEncode'];

            if($this->enabledFFI()) {    
                $this->shuffleFFI(InitArray::$func(), $base, $this->_to, false);
            }
            else {
                $this->shufflePHP(InitArray::$func(), $base, $this->_to, false);
            }            
        }

        return $this;
    }

    //First argument most be an ord bewteen 1 and 255 per index
    public function customFrom(array $singleBytes, int $baseLength, bool $shuffle = true) : self {

        if($baseLength < 4 || $baseLength > 255) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_ALPHABYTES, [
                    '%custom%' => 'customFrom'
                ])
            );  
        }

        $singleBytes = array_values($singleBytes); //make sure that the key is start from 0

        $count = count($singleBytes);
        if($count < $baseLength) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_ALPHABYTES, [
                    '%custom%' => 'customFrom'
                ])
            );  
        }

        //Binding custom
        $this->_from = "_customConfig256From";
        $this->{"lazyLoading" . $this->_from}();
        $this->_customConfig256From['base'] = $baseLength;
        $this->_customConfig256From['context'] = ShuffleProfile::DERIVATION_CUSTOM . sprintf('%03d', $baseLength);

        $shifting = Precompute::isBitshift($baseLength);
        if($shifting !== null) {
            $this->_customConfig256From['process'] = 'bitshift';         
            $this->_customConfig256From = array_merge($this->_customConfig256From, $shifting);
        }
        else {
            $this->_customConfig256From['process'] = 'compute';
            $this->_customConfig256From['exponentiation'] = Precompute::initBase($baseLength);

            if($this->_customConfig256From['exponentiation'] === null) {
                throw new ConfigurationException(InternalMessage::CONFIG_ERROR_DEFAULT);
            }

            $customConfig256From = &$this->_customConfig256From['exponentiation']['init'];
            for ($i=$this->_customConfig256From['exponentiation']['exp']; $i >= 0; $i--) { 
                $customConfig256From[] = \pow($baseLength, $i);
            }
        }

        //Shuffle
        if($shuffle === true) {
            //Reset previous shuffled alphabytes when already activated
            
            if(!empty($this->_custom256FromStr)) {
                MemeZero::overwriteString($this->_custom256FromStr);
                MemeZero::overwriteArray($this->_custom256From);
                MemeZero::overwriteArray($this->_custom256FromReverse);
            }

            if($this->enabledFFI()) {
                $this->shuffleFFI($singleBytes, $baseLength, $this->_from, true); 
            }
            else {
                $this->shufflePHP($singleBytes, $baseLength, $this->_from, true);
            }            
        }
        else {
            $this->setAlphabet($singleBytes, $baseLength, $this->_from);
        }

        $propName = $this->{$this->_from}['bindingStr'];
        if (strpos($this->{$propName}, "\0") !== false) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_PADDING_ZERO, [
                    'custom' => 'customFrom()'
                ])
            );
        }

        return $this;
    }

    //First argument most be an ord bewteen 1 and 255 per index
    public function customTo(array $singleBytes, int $baseLength, bool $shuffle = true) : self {
        if($baseLength < 4 || $baseLength > 255) {
            throw new ConfigurationException(
                    Merge::string(InternalMessage::INVALID_ALPHABYTES, [
                    '%custom%' => 'customTo'
                ])
            );  
        }

        $singleBytes = array_values($singleBytes); //make sure that the key is start from 0

        $count = count($singleBytes);
        if($count > 255 || $count < $baseLength) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_ALPHABYTES, [
                    '%custom%' => 'customTo'
                ])
            );  
        }

        //Binding custom
        $this->_to = "_customConfig256To";
        $this->{"lazyLoading" . $this->_to}();
        $this->_customConfig256To['base'] = $baseLength;
        $this->_customConfig256To['context'] = ShuffleProfile::DERIVATION_CUSTOM . sprintf('%03d', $baseLength);

        $shifting = Precompute::isBitshift($baseLength);
        if($shifting !== null) {
            $this->_customConfig256To['process'] = 'bitshift';   
            $this->_customConfig256To = array_merge($this->_customConfig256To, $shifting);
        }
        else {
            $this->_customConfig256To['process'] = 'compute';
            $this->_customConfig256To['exponentiation'] = Precompute::initBase($baseLength);

            if($this->_customConfig256To['exponentiation'] === null) {
                throw new ConfigurationException(InternalMessage::CONFIG_ERROR_DEFAULT);
            }
                    
            for ($i=$this->_customConfig256To['exponentiation']['exp']; $i >= 0; $i--) { 
                $this->_customConfig256To['exponentiation']['init'][] = \pow($baseLength, $i);
            }               
        }

        //Shuffle
        if($shuffle === true) {
            //Reset previous shuffled alphabytes when already activated
            if(!empty($this->_custom256ToStr)) {
                MemeZero::overwriteString($this->_custom256ToStr);
                MemeZero::overwriteArray($this->_custom256To);
                MemeZero::overwriteArray($this->_custom256ToReverse);
            }

            if($this->enabledFFI()) {
                $this->shuffleFFI($singleBytes, $baseLength, $this->_to, true);
            }
            else {
                $this->shufflePHP($singleBytes, $baseLength, $this->_to, true);
            }            
        }
        else {
            $this->setAlphabet($singleBytes, $baseLength, $this->_to);
        }

        $propName = $this->{$this->_to}['bindingStr'];
        if(strpos($this->{$propName}, "\0") !== false) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_PADDING_ZERO, [
                    'custom' => 'customTo()'
                ])
            );       
        }

        return $this;
    }

    public function longEndianChunk(bool $longEndianChunk) : self {
        $this->longEndianChunk = $longEndianChunk;
        return $this;
    }

    public function gmpEnable(bool $useGMP) : self {
        $this->_gmp = $useGMP;
        return $this;
    }

    /**** Magic Methods ****/
    public function __destruct() {
        $this->clearBaseConvert();
        $this->clearShuffleChars();
    }

    public function __debugInfo(): array
    {
        return ['key' => '*** hidden ***'];
    }
}