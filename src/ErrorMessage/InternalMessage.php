<?php
declare(strict_types=1);

namespace PswKey\ErrorMessage;

final class InternalMessage {

    public const CONFIG_ERROR_DEFAULT = 'Unexpected configuration error occurred'; 
    public const INVALID_EMPTY = "Variable %arg% may not be empty";
    public const INVALID_LIBSODIUM_CONTEXT = "Context name must be exactly 8 bytes";
    public const INVALID_DERIVE_LENGTH = "Derive-length must be at least 16 bytes"; 
    public const CHECK_VALIDATION_FAILED = "%check% found invalid input while validating %bases%";
    public const BUFFER_PROCESS_FAILED = "BufferProcess found invalid input while converting %bases%: invalid character '%char%' at position %pos%";
    public const LENGTH_REQUIRED = "At least %required% must be provided";
    public const WARNING_EMPTY = "Variable %arg% is empty - a default value is used";
    public const LIBSODIUM_REQUIRED = "Libsodium extension is required to use instance KeyStream()";
    public const DIGIT_PAIR_REQUIRED = "Imput must contain an even number of digits";
    public const INVALID_DIGITS = "Only digits 0-9 are allowed";
    public const INVALID_UNIFORM_CHUNK = "A non-uniform number was detected: a numeric chunk exceeds the 169-byte uniform limit after digit-to-byte conversion";
    public const INCOMPLETE_METHOD_CHAIN = "Method chain must include both From(x) and To(x)";
    public const RADIX_UNSUPPORTED = "%base% is not supported by default; use customFrom(x,y,z) instead";
    public const INVALID_PADDING_ZERO = "Invalid byte sequence in %custom%: zero byte (0x00) is not allowed";
    public const INVALID_ALPHABYTES = "Invalid length in %custom%: must be 4-255 bytes and ≥ base length";
    public const INVALID_FFI_PATH = "FFI failed to load: %path%";
    public const WARNING_FFI_FAILED = "Shuffle in C failed: PHP-Fallback is used for %base%";

    private function __construct() {}
}