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

This repository is deterministic by design. To maintain interoperability, Shuffle Profile Contracts should remain unchanged. Modifying them results in a divergent variant that should be treated as a private fork.

→ See: [DerivationProfile.php](../Modifiers/DerivationProfile.php)