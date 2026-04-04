<?php
declare(strict_types=1);

namespace PswKey\Service;

use PswKey\Exception\ConfigurationException;
use PswKey\Exception\InputException;
use PswKey\Interface\CustomKeyInterface;
use PswKey\Util\Math\Calculation;
use PswKey\Util\Secure\MemeZero;
use SensitiveParameter;

/**
 * Generates derived stream with a seed and optional key
 */
class KeyStream implements CustomKeyInterface {

    //Secret base 32 bytes from secretPhrase
    protected string $_mainKey;
    protected ?string $_customKey = null;

    //Secret streamkey 32 bytes: generated when needed
    private ?string $_streamKey = null;

    //Derive stream ID's
    private int $_streamID = 1;

    public function __construct(#[SensitiveParameter] string $seedPhrase, #[SensitiveParameter] string $keyPhrase = '') {

        $this->availability();

        $key = '';
        if(!empty($keyPhrase)) {
            $key = \sodium_crypto_generichash($keyPhrase, '', SODIUM_CRYPTO_KDF_KEYBYTES);
        }

        $this->_mainKey = \sodium_crypto_generichash($seedPhrase, $key, SODIUM_CRYPTO_KDF_KEYBYTES);
        MemeZero::overwriteString($key);
    }

    protected function availability() : void {
        /**
         * Optional feature checks:
         * If you are certain that GMP, Libsodium is available,
         * you may comment out or remove this section in constructor
         */
        if(!function_exists('sodium_memzero')) {
            throw new ConfigurationException('Sodium extension is required to use new KeyStream()');
        }
    }

    //Get main streamkey
    protected function setStreamKey() : void {

        if(empty($this->_streamKey)) {
            $this->_streamKey = sodium_crypto_kdf_derive_from_key(
                32, 
                256,
                "MyStream",
                $this->_mainKey
            ); 
        }
    }

    final protected function clearKeyStream() : void {
        MemeZero::overwriteBulkString(
            [$this->_mainKey, $this->_customKey, $this->_streamKey]
        );        
        unset($this->_mainKey, $this->_customKey, $this->_streamKey);
    }

    //Generate derivation key up to shuffle 256 indices with posibility of rejection sampling
    public function derivedKey(callable $callback, int $length, string $context, bool $customEnable = false) : void {

        if($length < 1 || $length > 256) {
            throw new InputException("Length only between 1 to 256 excepted");  
        }

        if(mb_strlen($context, '8bit') !== 8) {
            throw new ConfigurationException("Context name must be exactly 8 bytes");  
        }

        //Include bytes intended for rejection sampling
        $length = Calculation::getFactor($length);
        $buffer = [];

        $len = 64;
        $id = 1;
        do {
            if($length < 64) {
                $len = $length % 64;
                $len = $len < 16 ? 16 : $len;    
            }

            $buffer[$id] = sodium_crypto_kdf_derive_from_key(
                $len,
                $id, 
                $context,
                $customEnable === false ? $this->_mainKey : ($this->_customKey !== null ? $this->_customKey : $this->_mainKey)
            );

            $length -= $len;
            $id += 1;

        } while ($length > 0);

        $derivedKey = implode('', $buffer);        
        MemeZero::overwriteArray($buffer);

        try{ 
            $callback($derivedKey);
        }
        finally {
            MemeZero::overwriteString($derivedKey);
            unset($derivedKey);
        }
    }

    public function byteStream(callable $callback, int $length, ?string $context = null, int $streamID = 0) : void {

        if($context === null || mb_strlen($context, '8bit') !== 8) {
            $context = 'derive_b';
        }

        //Default at least 1 byte length required
        if($length < 1) {
            $length = 256;
        }

        //New or derived stream key
        if($streamID > 0) {
            $id = $streamID;
        }
        else {
            $id = $this->_streamID++;
        }

        //Rotated new nonce or derived by same streamID
        $subkey = sodium_crypto_kdf_derive_from_key(
            24, 
            $id, 
            $context,
            $this->_customKey !== null ? $this->_customKey : $this->_mainKey
        );

        $this->setStreamKey();

        //Stream random bytes
        $streamByte = sodium_crypto_stream(
            $length,
            $subkey,
            $this->_streamKey
        );      

        try {
            $callback($streamByte);
        }
        finally {
            MemeZero::overwriteBulkString([$subkey,$streamByte]);
            unset($subkey,$streamByte);
        }
    }

    public function setCustomKey(string $seedPhrase) : self {
        $this->_customKey = \sodium_crypto_generichash($seedPhrase, $this->_mainKey, SODIUM_CRYPTO_KDF_KEYBYTES);
        return $this;
    }

    public function resetCustomKey() : self {
        $this->_customKey = null;
        return $this;
    }

    public function __destruct() {
        $this->clearKeyStream();
    }

    public function __debugInfo(): array
    {
        return ['key' => '*** hidden ***'];
    }
}