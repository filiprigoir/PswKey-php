# Permutation
This document defines how permutation works in the repository.

---

## What is Permutation?
Permutation turns an integer ID into another integer ID and back again. It is a format-preserving permutation: the output is always a number in the same range as the input, so a 10-digit configuration never produces more than 10 digits.

The technical building blocks Format-Preserving Encryption (FPE) via Balanced Feistel Network:

- Format-Preserving Encryption: a reversible construction that mixes the bits of the ID.
- Balanced Feistel Network: keeps the result inside the configured range.

Properties you can rely on:

- Bijective: every ID in 0..max maps to exactly one output, every output comes from exactly one ID. 
- Unique: two different IDs never collide.
- Deterministic: the same ID with the same secretkey and configuration always gives the same output.
- Reversible: decode() returns the original ID.
- Range-bound: max is the largest number with the configured number of digits (for 10 digits: 9999999999).
- Supported lengths are 4 to 18 digits.

Permutation is meant to produce IDs that are not sequential and not guessable in order.


## How to use Permutation? 

There are two instance. The KeyStream() & Permutation() class.

```php
$keyStream = new KeyStream('SecretKey');
$permutation = new Permutation($keyStream);
$permutation->maxLength(10); //Default is 8 => 0 until 99999999, reset to 10 for example

$permutedId = $permutation->encode(123456);
$originalId = $permutation->decode($permutedId);
```

## How it works?

Encode() takes the original ID and returns the permuted ID. decode() does the opposite.

Rules:
- The input must be between 0 and maximum defined digit length of a number.
- The result is always an int in the same range.
- Use the same key and same digit configuration for encoding and decoding. 
- A different key or length gives a different mapping.


## Convert a Permutation ID output into a derived key
Permutation always returns an integer ID. Turning that ID into a key, token or any other representation is a separate step and is not part of the Feistel network.

Keep in mind:

- The result can have fewer digits than the configuration allows. With 10 digits (max = 9999999999) you can get 82495, but never a number above max.
- If you need a fixed length, pad the ID yourself, for example with str_pad((string) $id, 10, '0', STR_PAD_LEFT). When decoding, cast the padded value back with (int).


## Pswkey instance

Permutation gives you the ID. After that, you can decide to handle it into:

- Own key-conversion.
- An existing conversion such as base32 or base64.
- Keep the Permuted ID as it is.
- Adda modulo check
- etc.

Or use a PswKey instance via this repo, which converts the ID with a derived shuffled alphabet:

→ See: [System-defined](/src/Core/Documentation/Format/System.md)
→ See: [Custom-defined](/src/Core/Documentation/Format/Custom.md.md)

More about configuration possibility:

→ See: [ShuffleProfile.md](/src/Core/Documentation/Contract/ShuffleProfile.md)