<?php declare(strict_types=1);

/**
 * Test for Windows
 * Use terminal command: php __DIR__ . '/compiled/shuffleindice.dll'
 * Result with this derived key must be: 15 0 9 4 7 14 6 12 13 5 2 8 1 11 10 3
 */

if (!class_exists('FFI') || !extension_loaded('ffi')) {
    echo "[SKIP] FFI not enabled\n";
    exit(0);
}
else {
    $ffi = FFI::cdef('
        int shuffle_indices_secure(
            const uint8_t *rand_bytes,
            size_t rand_len,
            size_t input_len,
            size_t required_len,
            uint8_t *out_array
        );
    ', dirname(__DIR__, 2) . '/src/Core/Component/FFI/Compiled/shuffleindice.dll'); //Windows

    $inputLen = 16;
    $requiredLen = 16;
    $factor = 2;
    $randLen = $inputLen * $factor;

    /*
    * PHP derived bytes string
    */
    $deriveKey = \sodium_crypto_generichash('Test a derived key', '', SODIUM_CRYPTO_KDF_KEYBYTES);

    /*
    * Convert to C uint8_t array
    */
    $randBytes = FFI::new("uint8_t[$randLen]");
    FFI::memcpy($randBytes, $deriveKey, $randLen);

    $outArray = FFI::new("uint8_t[$requiredLen]");

    $func = "shuffle_indices_secure";
    $res = $ffi->{$func}(
        $randBytes,
        $randLen,
        $inputLen,
        $requiredLen,
        $outArray
    );

    if ($res !== 0) {
        die("C function error code: {$res}\n");
    }

    echo "Result: ";
    for ($i = 0; $i < $requiredLen; $i++) {
        echo $outArray[$i] . ' ';
    }
    echo PHP_EOL;

    /*
    * uniqueness validation
    */
    $unique = [];
    for ($i = 0; $i < $requiredLen; $i++) {
        $unique[$outArray[$i]] = true;
    }

    echo count($unique) === $requiredLen
        ? "UNIQUE OK\n"
        : "DUPLICATES FOUND\n";
}