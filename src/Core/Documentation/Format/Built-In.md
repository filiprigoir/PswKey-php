# Built-in vs Custom Lifecycle

This document describes how built-in conversion is configured and how alphabets are handled.

--- 

## Built-in (from() / to())

Built-in alphabets are persistent.

Once a base is requested:

```php
from(100)->to(62)
```

The alphabet shuffle for that base remains active during the PswKey-class lifecycle.

Characteristics:

- order of method calls does not matter
- uses the main key material
- deterministic across requests

based on:
- salt
- optional pepper

