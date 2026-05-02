<?php
declare(strict_types=1);

namespace PswKey\Core\Modifiers;

final class ImplementationType
{
    public const PHP = 'PHP';
    public const FFI = 'FFI'; 
    public const FALLBACK = 'PHP_FALLBACK';
    public const GMP = 'GMP';
    public const BC = 'BCMATH';

    private function __construct() {}
}