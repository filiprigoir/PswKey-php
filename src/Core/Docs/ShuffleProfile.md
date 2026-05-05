# Shuffle Profile Contract
A shuffle profile defines a deterministic derivation domain for the shuffle pipeline

This includes:
 
- rejection sampling behavior (FFI & PURE PHP)
- shuffle algorithm semantics in Core implementations
- entropy derivation or KeyStream sizing formulas
- normalization rules influenced by libsodium-derived entropy
- byte consumption order or chunking strategy

---

## Disclaimer

This repository is deterministic by design, but not mutation-safe across modified Shuffle Profile Contracts. Developers who alter Shuffle Profile Contract details must treat the resulting system as a new private derivation universe.

→ See: [DerivationProfile.php](../Modifiers/DerivationProfile.php)