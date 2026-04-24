<?php
declare(strict_types=1);

namespace PswKey\Util\Mapping;

final class Merge {

    private function __construct() {}

    public static function string(string $template, array $replacements) : string {
        return strtr($template, $replacements);
    }
}