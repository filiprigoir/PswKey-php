<?php
declare(strict_types=1);

namespace PswKey\Service;

use PswKey\Core\Modifiers\ShuffleProfile;
use PswKey\ErrorMessage\InternalMessage;
use PswKey\Exception\ConfigurationException;
use PswKey\Interface\CustomKeyInterface;
use PswKey\Util\Secure\MemeZero;
use SensitiveParameter;

/**
 * Generates derived stream with a seed (salt) and optional key (pepper)
 */
class KeyStream implements CustomKeyInterface {

    //Secret base 32 bytes from secretPhrase + keyphrase
    protected string $_mainKey;
    protected ?string $_customKey = null;

    //Secret streamkey 32 bytes: generated when needed
    private ?string $_streamKey = null;

    //Derive stream ID's
    private int $_seedId = 256;
    private int $_streamID = 1;

    public function __construct(#[SensitiveParameter] string $seedPhrase, #[SensitiveParameter] string $keyPhrase = '') {

        $this->availability();

        $key = '';
        if(!empty($keyPhrase)) {
            $key = \sodium_crypto_generichash($keyPhrase, '', SODIUM_CRYPTO_KDF_KEYBYTES);
        }

        $this->_seedId = strlen($seedPhrase);
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
            throw new ConfigurationException(
                InternalMessage::LIBSODIUM_REQUIRED
            );
        }
    }

    //Get main streamkey
    protected function setStreamKey() : void {

        if(empty($this->_streamKey)) {
            $this->_streamKey = sodium_crypto_kdf_derive_from_key(
                SODIUM_CRYPTO_KDF_KEYBYTES, 
                $this->_seedId,
                ShuffleProfile::DERIVATION_STREAM,
                $this->_mainKey
            ); 
        }
    }

    final protected function clearKeyStream() : void {
        MemeZero::overwriteBulkString(
            [$this->_mainKey, $this->_customKey, $this->_streamKey]
        );        
        unset($this->_mainKey, $this->_customKey, $this->_streamKey);
        $this->_seedId = 0;
    }

    /**
     * Generates a derived key with callback and context
     * * * a minimum overhead of +16 bytes
     * * * alignment to blocks of up to +64 bytes
     */
    public function derivedKey(callable $callback, int $length, string $context, bool $customEnable = false) : void {

        if($length < SODIUM_CRYPTO_KDF_BYTES_MIN) {
            throw new ConfigurationException(
                InternalMessage::INVALID_DERIVE_LENGTH
            );  
        }

        if($context === null || mb_strlen($context, '8bit') !== 8) {
            throw new ConfigurationException(
                InternalMessage::INVALID_LIBSODIUM_CONTEXT
            );  
        }

        $buffer = [];
        $len = 64;
        $id = $this->_seedId + $length;
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
            $id++;

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

    /**
     * Generates a stream of derived bytes with callback, context and streamID
     */
    public function byteStream(callable $callback, int $length, string $context, int $streamID = 0) : void {

        if(mb_strlen($context, '8bit') !== 8) {
            throw new ConfigurationException(
                InternalMessage::INVALID_LIBSODIUM_CONTEXT
            );  
        }

        if($length < SODIUM_CRYPTO_KDF_BYTES_MIN) {
            throw new ConfigurationException(
                InternalMessage::INVALID_DERIVE_LENGTH
            );  
        }

        //provided or increament streamID
        if($streamID > 0) {
            $id = $streamID;
        }
        else {
            $id = $this->_streamID++;
        }

        //Rotated new nonce subkey
        $subkey = sodium_crypto_kdf_derive_from_key(
            SODIUM_CRYPTO_STREAM_NONCEBYTES, 
            $id, 
            $context,
            $this->_customKey !== null ? $this->_customKey : $this->_mainKey
        );

        $this->setStreamKey();

        //Stream derived bytes
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

    /*
     * Provides the ability to set a secret customKey for deterministic custom converts
     * and streamByte generation instead of the secret mainkey.
     */
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