# Conversion Pipeline — Compute

This document describes how compute-based conversion is performed for bases that are not powers of two.

---

## Compute

Compute mode is used for bases that are **non-power-of-two bases**.

Examples:

- Base17
- Base45
- Base58
- Base62

Unlike bitshift-based conversions, compute mode uses positional chunk encoding with a base-specific configuration.

The exact mapping depends on:

- the target radix
- the selected configuration set

---

## Base-Specific Configuration

When a compute base is requested, the repository selects a configuration based on the target radix.

A configuration defines:

- input endian chunk size
- exponentiation depth
- output symbol length

Example:

```php
$base >= 43 && $base <= 46 => [
    "chunk"  => 14,
    "exp"    => 8,
    "symbol" => 9
];
```

This means:

- 14 digits are processed per chunk
- the chunk is decomposed through 8 exponentiation steps
- the result produces 9 output symbols

---

## Chunk Processing

Digits are processed in fixed-size chunks.

Example:

- [14 digits] → [9 symbols]
- [14 digits] → [9 symbols]

Each chunk is processed independently.

No computational state is shared between chunks.

During decoding, chunks may be left-padded to reconstruct the original fixed-width representation.

---

## Positional Encoding

Each chunk is encoded through positional decomposition using precomputed exponentiation values.

Example for Base62:

```php
3521614606208
56800235584
916132832
14776336
238328
3844
62
1
```

Each iteration decomposes the chunk into symbols using these positional weights.

After processing, the loop starts again with the next chunk.

---

## Unified behavior

Both system-defined and custo-defined bases follow the same routing logic for consistency.

---

## Selection rule

The repo automatically selects:

- Compute: non-power-of-two bases
- Bitshift: power-of-two bases

→ See: [Bitshift.md](/src/Core/Documentation/Pipline/Bitshift.md)