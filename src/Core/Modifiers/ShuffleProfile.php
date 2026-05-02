<?php
declare(strict_types=1);

namespace PswKey\Core\Modifiers;

/**
 * A Shuffle Profile ID defines the deterministic derivation contract of the shuffle pipeline
 */
final class ShuffleProfile
{
    //shuffle context: must be excactly 5 bytes here (radix will be added dynamically, e.g.: 64 -> 064)
    public const DERIVATION_CHARSET = 'V001B';
    public const DERIVATION_CUSTOM = 'V001C';

    //stream context: must be excactly 8 bytes
    public const DERIVATION_STREAM = 'STR001KY';

    //onetimepad default context: must be excactly 8 bytes
    public const DEFAULT_OTP_BYTES = 'OTP001BY';
    public const DEFAULT_OTP_DIGITS = 'OTP001DI';

    //byte chunk: mathematically designed to minimize leading padding
    public const ENDIAN_CHUNK_LONG = [169, 407];
    public const ENDIAN_CHUNK_SHORT = [22, 53];   

    private function __construct() {}
}