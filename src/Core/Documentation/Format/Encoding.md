# Encoding Input And Output
This document defines how conversion input and output should be interpreted.

---
 
## UTF input

UTF input must first be converted to single-byte representation before conversion.

The conversion pipeline does not transcode text automatically.

Use:

```php 
use PswKey\Util\Char\Transcode;

$singleBytes = Transcode::getISO($input, x);
```

---

## Single-byte output

Conversion output is returned as single-byte symbols (1 byte per symbol), not as UTF text.

Bytes above 127 (0x80–0xFF) may display incorrectly when interpreted directly as UTF text (e.g. �).

For readable UTF output, use:

```php 
use PswKey\Util\Char\Transcode;

$singleBytes = Transcode::getUTF($output, x);
```

This is required when the input may contain byte values above 127 (0x80–0xFF).

---

## Raw Bytes

Raw byte input does not require transcoding.

Applying UTF/ISO transcoding to raw bytes may produce invalid results

---

## Custom en System-defined

The same byte rules apply to both conversion modes.

Examples:

from(100)
customFrom(100)

Bases with extended symbol ranges may contain byte values above 127.
