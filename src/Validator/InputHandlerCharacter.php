<?php
declare(strict_types=1);

namespace PswKey\Validator;

use PswKey\Exception\InputException;

/**              
* Inject ErrorHandling-methods in class Character
*/
trait InputHandlerCharacter {

    use InputHandler;

    protected function checkBase(string $data, int $len, ?string $allowed, array $configFrom) : bool {

        ['base' => $base, 'checksum' => $checksum] = $configFrom;

        $checkNumber = min($len / 2, 64);

        if($checksum && $base === 100) {
            if($checkNumber < 0.50) {   
                throw new InputException(
                    "At least one character must be entered/Character input is too short"
                );
            }
        }
        elseif($checkNumber < 1) {
            throw new InputException(
                "At least two characters must be entered/Character input is too short"
            );
        }

        $snip = (int)ceil($checkNumber);
        if($checksum && $base === 10) {
            return \ctype_digit(\substr($data, 0, $snip)) && \ctype_digit(\substr($data, -$snip));
        }
        else {
            return strspn($data, $allowed, 0, $snip) === $snip
                && strspn($data, $allowed, $len - $snip, $snip) === $snip;
        }
    }

    protected function fullCheck(string $str, $allowed, int $len) : bool {
        return strspn($str, $allowed) === $len;
    }
}