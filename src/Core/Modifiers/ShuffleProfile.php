<?php
declare(strict_types=1);

namespace PswKey\Core\Modifiers;

/** A ShuffleProfile version defines the deterministic derivation contract of the shuffle pipeline.
 *
 * The version (v001) must be incremented whenever any logic affecting the resulting
 * shuffle output changes, including:
 * 
 * * rejection sampling behavior (FFI & PURE PHP)
 * * implementation-level shuffle semantics in the Core-classes
 * * entropy derivation sizing formulas in the KeyStream-class
 * * libsodium normalization rules
 * * byte/endian chunking consumption order in the shuffle pipeline
*/
final class ShuffleProfile
{
    //radix context: must be excactly 5 bytes here (parameter will be added dynamically, ie.: 64 -> 064)
    public const DERIVATION_STANDARD = 'V001B';
    public const DERIVATION_CUSTOM = 'V001C';

    //stream context: must be excactly 8 bytes
    public const DERIVATION_STREAM = 'V001S001';

    //onetimepad default: must be excactly 8 bytes
    public const DEFAULT_OTP_BYTES = 'D001OTPB';
    public const DEFAULT_OTP_DIGITS = 'D001OTPD';

    //byte chunk: mathematically designed to minimize leading padding
    public const ENDIAN_CHUNK_LONG = [169, 407];
    public const ENDIAN_CHUNK_SHORT = [22, 53];   

    private function __construct() {}
}