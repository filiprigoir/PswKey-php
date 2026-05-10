# Custom Conversion Pipeline

This document describes how custom conversion is configured and how user-defined alphabets are handled.

---

## Overview

Custom conversion allows developers to provide their own alphabet definition instead of using the built-in from() and to() alphabet system.

Configuration is performed through:

- Encode:

```php
customTo(array $singleBytes, int $baseLength, bool $shuffle = true): self;
```

- Decode:

```php
customFrom(array $singleBytes, int $baseLength, bool $shuffle = true): self;
```

### array $singleBytes

Defines the custom alphabet.

The array must contain unique single-byte values only.
Unique Array is responsibility of the client, not checked by system

Requirements:

- no UTF-8 multibyte sequences
- no prefixed unicode values
- ISO-style single-byte input only

Possible input methods:

* UTF-8 → ISO conversion:

```php
$string = 'abcde'; //most be minium 4
$bytes  = str_split(transcode::getISO($string));
```
* Integer loop with chr():

```php
for ($i = 1; $i < 255; $i++) {
    $bytes[] = chr($i);
}
```
* Manual byte definition:

```php
[
    "\x31",
    "\x32",
    "\x33"
]
```

---

### int $baseLength

Defines the effective radix size used during conversion.

The provided alphabet may contain more symbols than required.

Example:

- 62-byte alphabet provided
- requested base size: 32

In this case:

- the alphabet is shuffled
- an index with fisher-yates and rejection sampling is selected
- the alphabet is sliced to the requested radix length

→ More about using fisher-Yates: [Deterministic.md](/src/Core/Documentation/Contract/Deterministic.md)

---

## bool $shuffle

Controls alphabet randomization.

true:
- alphabet is shuffled deterministically (via seedphrase and optional keyphrase)
- uses the active key derivation system

false:
- preserves original alphabet order
- disables shuffle randomization

---

## Function call customFrom() and customTo()

Custom alphabets are rebuilt on every call.

Each invocation:

- resets the previous custom alphabet state
- rebuilds the alphabet configuration
- recalculates shuffle mappings

Reason:

- base size may change dynamically
- alphabet definitions may change dynamically
- custom keys may change between calls

based on:
- salt
- optional pepper

---

# Custom Key Derivation

Custom conversion supports an optional custom key.

The custom key defaults to `null` and can be configured through a fluent interface.

When present:

- the custom key participates in shuffle derivation
- relates to customFrom() and customTo() only
- the main key remains active for built-in from() and to() bases
- both keys are internally combined through Libsodium

Characteristics:

- deterministic derivation
- isolated custom shuffle domains
- dynamic reconfiguration support

If no custom key is configured via fluent interface, the system uses the main key derivation only.
