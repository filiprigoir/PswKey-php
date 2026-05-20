# Custom-defined Alphabet

This document describes how custom conversion is configured and how client-defined alphabets are handled.

---

## Overview

Custom conversion allows developers to provide their own alphabet definition instead of using the system-defined from() and to() alphabet system.

Configuration is performed through:

- Encode:

```php
customTo($singleBytes, $baseLength, $shuffle = true);
```

- Decode:

```php
customFrom($singleBytes, $baseLength, $shuffle = true);
```

### array $singleBytes

Defines the custom alphabet.

The array must contain unique single-byte values only.

Requirements:

- no UTF-8 multibyte sequences
- no prefixed unicode values
- ISO-style single-byte input only

Possible input methods:

* UTF-8 → ISO conversion:

```php
$string = 'abcµde'; //most be minium 4
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
    "\xb4",
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
If no custom key is configured, the system uses the main key derivation only.

When present:

- the custom key participates in shuffle derivation
- only relates to customFrom() and customTo()
- the main key remains active for system-defined from() and to() bases

Characteristics:

- deterministic derivation
- isolated custom shuffle domains
- dynamic reconfiguration support

---

## System-defined vs Custom-defined Lifecycle

System-definedn alphabets are persistent.

Once a base is requested:

```php
from(100)->to(62)
```

The alphabet shuffle for that base remains active during the PswKey-class lifecycle.

Characteristics:

- order of method calls does not matter
- order of bases does not matter
- uses always the main key
