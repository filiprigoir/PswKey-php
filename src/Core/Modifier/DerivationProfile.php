<?php
declare(strict_types=1);

namespace PswKey\Core\Modifier;

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

    public static function getContextCharset(int $radix) : string {
        $context = getenv('PSWKEY_CONTEXT_CHARSET');
        if($context !== false && mb_strlen($context, '8bit') === 5) {
            return $context . str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
        }

        return self::SHUFFLE_DEFAULT_CHARSET . str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
    }

    public static function getContextCustom(int $radix) : string {    
        $context = getenv('PSWKEY_CONTEXT_CUSTOM');
        if($context !== false && mb_strlen($context, '8bit') === 5) {
            return $context . str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
        }
        return self::SHUFFLE_CUSTOM_ALPHABET . str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
    }

    public static function getContextStream() : string {
        $context = getenv('PSWKEY_CONTEXT_STREAM');
        if($context !== false && mb_strlen($context, '8bit') === 8) {
            return $context;
        }
        return self::DERIVATION_STREAM;
    }

    private function __construct() {}
} 