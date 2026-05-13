# Conversion Pipeline — Bitshift

This document describes base conversion using the bitshift method for power-of-two bases.

---

## Bitshift

bitshift mode is used for bases that are **power-of-two bases**.
Used for bases that are powers of two
(e.g. 2, 4, 8, 16, 32, 64)

Bitshift conversion relies on binary operations instead of arithmetic division.

Characteristics:

- Fastest conversion path
- Direct binary manipulation
- No modulo/division required

---

## Base-Specific Configuration

When a compute base is requested, the repository selects a configuration based on the target radix.

---

## Unified behavior

Both system-defined and custom-defined bases follow the same routing logic for consistency.

---

## Selection rule

The repo automatically selects:

- Bitshift: power-of-two bases
- Compute: non-power-of-two bases

→ See: [Compute.md](/src/Core/Documentation/Pipline/Compute.md)



