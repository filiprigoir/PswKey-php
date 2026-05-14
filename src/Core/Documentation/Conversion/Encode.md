# Encode
This document defines how encode conversion works in the repository.

---

## What is encode?

Encode is the output of an original message in the conversion functies that is converted to a stransport key (symbols).

## Conversion input

The input represents the original normalized value domain.

Supported input domains:

- Base10
- Base100
- Base256

→ for normalization: [Normalization.md](/src/Core/Documentation/Pipline/Normalization.md)

## Conversion output

The output represents the encoded symbol domain.

Supported output domains:

- Base10 (from 100)
- Base100 (from 10)
- BaseX (X = 62, 32, etc.)

## UTF and single-byte handling

Handling of UTF text and single-byte conversion is documented separately.

→ See: [Encoding.md](/src/Core/Documentation/Format/Encoding.md)
