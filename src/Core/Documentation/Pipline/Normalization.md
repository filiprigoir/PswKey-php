# Conversion Pipeline — Normalization

This document describes the normalization stage used in the conversion pipeline.

---

## Normalization

Normalization converts raw bytes input (`Base256`) into the repository’s canonical `Base100` representation.

The normalization process is implemented through **Big-Endian Chunking**.

Pipeline:

```text
Base256
   ↓
Big-Endian Chunking
   ↓
Numeric Representation (not base10 but Big Endian chunks)
   ↓
Base100
```

---

## Big-Endian Chunking

Raw bytes are processed in fixed-size chunks.

Supported chunk sizes:

| Mode | Bytes | Digits | boolean | through |
|---|---|---|---|---|
| Small Chunk | 22 bytes | 53 digits | false | fluent interface
| Large Chunk | 169 bytes | 407 digits | true | fluent interface

Each chunk is interpreted as a Big-Endian integer before being converted into Base100.

--- 

## Configure chunk property

This property can be configured via a fluent interface longEndianChunk(bool):

- `$_endianChunk = true`  
  → 169 bytes → 407 digits per iteration  

- `$_endianChunk = false`  
  → 22 bytes → 53 digits per iteration  

---

## Uniformity

A chunk is considered **uniform** when decoding always reconstructs the original byte length without overflow.

Supported uniform chunk mappings:

- 22 bytes → 53 digits
- 169 bytes → 407 digits

However, every endian chunk has a strict upper bound.

### Example — 169-byte chunks

Not every possible 407-digit sequence represents a valid 169-byte value.

Digit sequences exceeding the maximum representable range of a 169-byte chunk expand into 170 bytes during reconstruction (overflow).

Such overflow is classified as **non-uniform** and rejected during reconstruction.

---

## Purpose

Base100 only supports:

- alphabetic characters
- numeric characters
- 38 predefined symbols

system-defined Base100 uses the following character set:

```text
0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"#$%&'()*+,-./:;\=´?@[]^_`{|}~£§¨²³µ°
```

Base100 does not support:

- raw bytes
- whitespace
- `<` and `>`

To support (arbitrary) raw binary input, the repository introduces Base256.

Base256 allows raw data to be normalized into a deterministic Base100 representation before further processing.