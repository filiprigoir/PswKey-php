# Decode
This document defines how decode conversion works in the repository.

---

## What is Decode?

Decode is the conversion of a transport representation back into its original representation.

## Conversion input

The input represents the encoded symbol domain.

Supported input domains:

- Base10 (from 100)
- Base100 (from 10)
- BaseX (X = 62, 32, etc.)

## Conversion output

The output represents the original normalized value domain.

Supported output domains:

- Base10
- Base100
- Base256

→ for normalization: [Normalization.md](/src/Core/Documentation/Pipline/Normalization.md)

## UTF and single-byte handling

Handling of UTF text and single-byte conversion is documented separately.

→ See: [Encoding.md](/src/Core/Documentation/Format/Encoding.md)
