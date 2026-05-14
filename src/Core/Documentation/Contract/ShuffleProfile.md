
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

The derivation context may also be customized through the setters to create an isolated deterministic own service.

```php
DerivationProfile::setContextCharset(requires_5_bytes); //becomes 8 bytes (ie.: 64 => 064)
DerivationProfile::setContextCustom(requires_5_bytes); //becomes 8 bytes (ie.: 100 => 100)
DerivationProfile::setContextStream(requires_8_bytes);
```

Note: Default and customized derivation contexts are equally valid and secure. Different context configurations simply produce different deterministic outputs and can be used for private deterministic domain isolation.

→ See: [DerivationProfile.php](/src/Core/Modifier/DerivationProfile.php)
