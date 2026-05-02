# Deterministic Shuffle (FFI + PHP)

## Overview

This component provides a deterministic index shuffle based on a cryptographic entropy source and ShuffleProfile (directory: Modifiers) configuration.

It combines:

* a Fisher–Yates shuffle using rejection sampling
* a circular slice selection on the shuffled result

Two execution backends are supported:

* Native implementation via PHP FFI (preferred)
* Pure PHP implementation (fallback)

Both implementations are functionally equivalent and produce identical results.

## Why

The FFI implementation provides stricter memory control than PHP, including explicit buffer zeroing. PHP uses best-effort cleanup.

When FFI is unavailable, disabled, or misconfigured, the system automatically falls back to the PHP implementation.

The fallback preserves functional correctness and determinism, but does not guarantee identical memory behavior.

## Algorithm

The algorithm operates as follows:

* initialize a sequential index buffer `[0..N-1]`
* perform a Fisher–Yates shuffle using rejection sampling to avoid modulo bias
* consume entropy bytes sequentially during the shuffle
* derive a start index from the next entropy bytes
* return a contiguous subset of length `K` from the shuffled buffer

## Entropy Model

All randomness is derived from a single entropy buffer generated via KeyStream-class with libsodium.
The entropy buffer must be sufficiently large relative to the shuffle size.

If the entropy buffer is too small, it will be reused cyclically, which may reduce the statistical quality of the shuffle and introduce unintended correlations.

## Constraints

* minimum input length: 4
* maximum input length: 256
* maximum base: 252

The entropy buffer is scaled relative to the input size using a factor.

The sizing formula in the ShuffleChars (ShuffleFFI & ShufflePHP) is:

* factor = 1.3 + (N / 256) * 0.25;
* KeyLength = ceil(N * factor);

This compensates for rejection sampling, where bytes above the largest unbiased range for the selected base are discarded.

The resulting size is then adjusted by the KeyStream (derivedKey) implementation to satisfy libsodium's minimum and maximum derivation limits:

A minimum remainder of +16 bytes is enforced when a partial block exists
full blocks are grouped in 64-byte segments.

Examples:

* 6 → 8 → 16 (1 * 16)
* 58 → 79 → 80 (1 * 64 + 16)
* 129 → 184 → 184 (2 * 64 + 56)

The first value is the requested length, the second is the entropy size after rejection sampling compensation, and the final value is the normalized size adjusted to comply with libsodium's minimum length constraints.

## Platform Support

Precompiled native binaries are provided for:

- Windows (`.dll`)
- Linux (`.so`)
- macOS Apple Silicon (`.dylib`)
- macOS Intel / legacy (`.dylib`)

## Maintenance Notes

* Any change to the algorithm must preserve output parity between PHP and FFI implementations
* The entropy consumption order MUST remain identical across implementations
* The rejection sampling logic MUST remain consistent to avoid bias