# Shuffle Profile Contract
A ShuffleProfile version defines the deterministic derivation contract of the shuffle pipeline

## Shuffle Profile ID

The shuffle profile id (v001) must be incremented when any change affects the resulting output sequence

This includes (but is not limited to):
 
- rejection sampling behavior (FFI & PURE PHP parity layer)
- shuffle algorithm semantics in Core implementations
- entropy derivation or KeyStream sizing formulas
- normalization rules influenced by libsodium-derived entropy
- byte consumption order or chunking strategy

## Maintenance Notes

Non-output-affecting changes (refactoring, optimization without
behavioral impact) must not trigger a version bump

→ See: [ShuffleProfile.php](../Modifiers/ShuffleProfile.php)