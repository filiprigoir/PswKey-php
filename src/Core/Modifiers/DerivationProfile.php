<?php
declare(strict_types=1);

namespace PswKey\Core\Modifiers;

/**
 * A Derivation Profile defines the deterministic derivation contract of the derivation pipeline
 */
final class DerivationProfile
{
    //derivation context: must be excactly 5 bytes here (radix will be added dynamically, e.g.: 64 -> 064)
    private const DERIVATION_CHARSET = 'DECHA';
    private const DERIVATION_CUSTOM = 'DECUS';

    //stream context: must be excactly 8 bytes
    public const DERIVATION_STREAM = 'DERSTRKY';

    //onetimepad default context: must be excactly 8 bytes
    public const DEFAULT_OTP_BYTES = 'DEFOTPBY';
    public const DEFAULT_OTP_DIGITS = 'DEFOTPDI';

    //byte chunk: mathematically designed to minimize leading padding
    public const ENDIAN_CHUNK_LONG = [169, 407];
    public const ENDIAN_CHUNK_SHORT = [22, 53];

    public static function getContextCharset(int $radix) : string {
        return self::DERIVATION_CHARSET . str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
    }

    public static function getContextCustom(int $radix) : string {
        return self::DERIVATION_CUSTOM . str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
    }

    private function __construct() {}
}