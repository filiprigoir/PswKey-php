# Conversion Pipeline
This document describes how data flows through the system,
including normalization, chunking, and encoding strategies.

## Big-Endian Chunking

Controls the internal chunk size used during conversion for Base100 and Base256 inputs.  
Default and custom conversions use the same underlying system.

---

## Input Normalization

Base100 acts as the canonical intermediate format.

- Base256 is always normalized to Base100 before further processing

The two primary input formats are:

- **Base256** → binary data  
- **Base100** → text-based representation (e.g. password text)

---

## Conversion Types

There are three types of conversion:

### Precompute
Internal formats:
- Base256
- Base100

---

### Compute
Bases that are **not powers of two**  
(e.g. 17, 45, 58, 62)

---

### Bitshift
Bases that **are powers of two**  
(e.g. 2, 4, 32, 64)

---

## When Chunking Is Applied

Chunking is always used for Base256, and conditionally used for Base100.

---

### 🔹 Base256 → Base32

- `from(256)` first normalizes input to Base100  
- Byte chunking (big-endian) is applied  
- `to(32)` uses bitshift for final conversion  

---

### 🔹 Base100 → Base32

- `from(100)` does **not** use big-endian chunking  
- Conversion is handled directly via bitshift  

---

### 🔹 Base100 → Base62

- `from(100)` applies big-endian processing  
- Required to convert into a compute-based format (Base62)

---

## Chunking of Binary Data

During processing, binary data is chunked as follows:

- `$_endianChunk = true`  
  → 169 bytes → 407 digits per iteration  

- `$_endianChunk = false`  
  → 22 bytes → 53 digits per iteration  

This property can be configured via a fluent interface.

---

## Digit Chunking

Applied when converting to a compute base.

- A base-specific configuration is selected based on the radix  
- Digits are split into fixed-size chunks  
- Each chunk is encoded into symbols  

The exact mapping depends on the target base.

---

## Derivation Profile

This workflow is part of the shuffle contract.

→ See: [DerivationProfile.php](../Modifiers/DerivationProfile.php)

