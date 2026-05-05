# Core System Documentation

This section describes the internal Core architecture of the encoding and transformation system.

The Core is responsible for deterministic byte-level operations, including base conversion, shuffling, and entropy-driven transformations.

---

## Modules

### 🔹 Contract of the shuffle pipeline
Defines a deterministic derivation domain for the shuffle pipeline

→ See: [ShuffleProfile.md](./ShuffleProfile.md)

---

### 🔹 Encoding & Single-Bytes
Handles single-byte representations and not-standalone UTF-8 bytes

→ See: [Encoding.md](./Encoding.md)

---

### 🔹 Pipline conversion Endian & Exponentiation Chunk
Describes how data flows through the conversion algorithm

Inlcudes:
- endian Chunking
- exponentiation Chunking
- encoding strategies
- normalization

→ See: [Pipeline.md](./Pipline.md)

---

### 🔹 Determinisic & KeyStream
Defines how randomness is generated and consumed across operations.

Includes:
- keystream generation
- entropy sizing rules
- reuse behavior constraints

→ See: [Deterministic.md](./Deterministic.md)

---

### 🔹 Custom
Defines system limits and invariants that must be preserved across all implementations.

Includes:
- input/output bounds
- base limits
- parity requirements between PHP and FFI

→ See: [Custom.md](./Custom.md)

---

### 🔹 Encode
Defines system limits and invariants that must be preserved across all implementations.

Includes:
- input/output bounds
- base limits
- parity requirements between PHP and FFI

→ See: [Encode.md](./Encode.md)

---

### 🔹 Decode
Defines system limits and invariants that must be preserved across all implementations.

Includes:
- input/output bounds
- base limits
- parity requirements between PHP and FFI

→ See: [Decode.md](./Decode.md)

---

## Implementation Note

Core logic is implementation-agnostic.

Both PHP and FFI layers must produce identical results and adhere strictly to the rules defined in this section.