<?php
declare(strict_types=1);

namespace PswKey\Core\Modifiers;

/**
 * Which implementation is used?
*/
final class ImplementationType
{
    public const PHP = 'PHP';
    public const FFI = 'FFI'; 
    public const FALLBACK = 'PHP_FALLBACK';
    public const GMP = 'GMP';
    public const BC = 'BC';

    private function __construct() {}
}