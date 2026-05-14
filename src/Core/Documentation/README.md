# Core System Documentation

This section describes the internal Core architecture of the encoding and transformation system.

The Core is responsible for deterministic byte-level operations, including base conversion, shuffling, and entropy-driven transformations.

---

## Modules

### 🔹 Deterministic Shuffle contract (FFI + PHP)
Defines a deterministic derivation domain for the shuffle pipeline

→ See: [ShuffleProfile.md](/src/Core/Documentation/Contract/ShuffleProfile.md)

---

### 🔹 Determinisic & KeyStream
Defines how randomness is generated and consumed across operations.

Includes:
- keystream generation
- entropy sizing rules
- reuse behavior constraints

→ See: [Deterministic.md](/src/Core/Documentation/Contract/Deterministic.md)

---

### 🔹 Encode
Defines how encode conversion works in the repository.

Includes:
- input/output bounds
- base limits

→ See: [Encode.md](/src/Core/Documentation/Conversion/Encode.md)

---

### 🔹 Decode
Defines how decode conversion works in the repository.

Includes:
- input/output bounds
- base limits

→ See: [Decode.md](/src/Core/Documentation/Conversion/Decode.md)

---

### 🔹 System-Defined
Defines the system-defined alphabets used by the conversion system.

→ See: [System.md](/src/Core/Documentation/Format/System.md)

---

### 🔹 Custom-Defined
Describes how custom conversion is configured and how client-defined alphabets are handled.

→ See: [Custom.md](/src/Core/Documentation/Format/Custom.md)

---

### 🔹 Encoding
Handles single-byte representations and not-standalone UTF-8 bytes

→ See: [Encoding.md](/src/Core/Documentation/Format/Encoding.md)

---

### 🔹 Pipline conversion Endian & Exponentiation Chunk
Describes the normalization stage used in the conversion pipeline.

Inlcudes:
- bytes chunking
- endian Chunking
- encoding mechanisms
- normalization

→ See: [Normalization.md](/src/Core/Documentation/Pipline/Normalization.md)

---

### 🔹 Bitshift
Describes how compute-based conversion is performed for bases that are not powers of two.

→ See: [Bitshift.md](/src/Core/Documentation/Pipline/Bitshift.md)

---

### 🔹 Compute
Describes how compute-based conversion is performed for bases that are not powers of two.

→ See: [Compute.md](/src/Core/Documentation/Pipline/Compute.md)

---

## Implementation Note

Core logic is implementation-agnostic.

Both PHP and FFI layers must produce identical results and adhere strictly to the rules defined in this section.