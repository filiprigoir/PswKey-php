<?php
declare(strict_types=1);

namespace PswKey\Core\Modifier;

use PswKey\ErrorMessage\InternalMessage;
use PswKey\Exception\ConfigurationException;
use PswKey\Util\Base\Radix;
use PswKey\Util\Mapping\Merge;

/**
 * A Derivation Profile defines the deterministic derivation contract of the derivation pipeline
 */
final class DerivationProfile
{
    //shuffle profile: must be excactly 5 bytes here (radix will be added dynamically, e.g.: 64 -> 064)
    private const SHUFFLE_DEFAULT_CHARSET = 'SDEFC';
    private const SHUFFLE_CUSTOM_ALPHABET = 'SCUSA';

    //stream profile: must be excactly 8 bytes
    private const DERIVATION_STREAM = 'DERSTRKY';

    //onetimepad default profile: must be excactly 8 bytes
    public const DEFAULT_OTP_BYTES = 'DEFOTPBY';
    public const DEFAULT_OTP_DIGITS = 'DEFOTPDI';

    //byte chunk: mathematically designed to minimize leading padding
    public const ENDIAN_CHUNK_LONG = [169, 407];
    public const ENDIAN_CHUNK_SHORT = [22, 53];

    //optional bootstrap context
    private static ?string $_contextCharset = null;
    private static ?string $_contextCustom = null;
    private static ?string $_contextStream = null;

    public static function getContextCharset(int $radix) : string {
    
        $concat = Radix::bindRadix($radix);

        if(self::$_contextCharset !== null) {
            return self::$_contextCharset . $concat;
        }
        return self::SHUFFLE_DEFAULT_CHARSET . $concat;
    }

    public static function getContextCustom(int $radix) : string {    
    
        $concat = Radix::bindRadix($radix);

        if(self::$_contextCustom !== null) {
            return self::$_contextCustom . $concat;
        }
        return self::SHUFFLE_CUSTOM_ALPHABET . $concat;
    }

    public static function getContextStream() : string {
        
        if(self::$_contextStream !== null) {
                return self::$_contextStream;
        }
        return self::DERIVATION_STREAM;
    }

    public static function setContextCharset(string $context) : void {
 
        if($context === null) {
            self::$_contextCharset = null;
        }
        
        if(mb_strlen($context, '8bit') !== 5) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_LIBSODIUM_CONTEXT, 
                    ["%length%" => 5]
                )
            );
        }

        self::$_contextCharset = $context;
    }

    public static function setContextCustom(string $context) : void {

        if($context === null) {
            self::$_contextCustom = null;
        }

        if(mb_strlen($context, '8bit') !== 5) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_LIBSODIUM_CONTEXT, 
                    ["%length%" => 5]
                )
            );
        }
        self::$_contextCustom = $context;
    }

    public static function setContextStream(string $context) : void {

        if($context === null) {
            self::$_contextStream = null;
        }

        if(mb_strlen($context, '8bit') !== 8) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::INVALID_LIBSODIUM_CONTEXT, 
                    ["%length%" => 8]
                )
            );
        }
        self::$_contextStream = $context;
    }

    private function __construct() {}
} 