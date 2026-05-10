
# Shuffle Profile Contract

A shuffle profile defines a deterministic derivation domain for the shuffle pipeline.

This includes:

- rejection sampling behavior (FFI & PURE PHP)
- shuffle algorithm semantics in Core implementations
- entropy derivation and KeyStream sizing formulas
- normalization rules influenced by libsodium-derived entropy
- byte consumption order and chunking strategy

---

## Derivation Contract Configuration

This repository is deterministic by design.

By default, the standard derivation context is used to preserve interoperability between installations.

The derivation context may also be customized through environment configuration to create an isolated deterministic namespace.

Environment configuration variables are:

```php
PSWKEY_CONTEXT_CHARSET=requires_5_bytes
PSWKEY_CONTEXT_CUSTOM=requires_8_bytes
PSWKEY_CONTEXT_STREAM=requires_8_bytes
```

Note: Default and customized derivation contexts are equally valid and secure. Different context configurations simply produce different deterministic outputs and can be used for private deterministic domain isolation.

→ See: [DerivationProfile.php](/src/Core/Modifier/DerivationProfile.php)
