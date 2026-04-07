<?php
declare(strict_types=1);

namespace PswKey\Interface;

interface ConvertBaseInterface {
    function convert(string $mix) : ?string;
    function from(int $base) : self;
    function to(int $base) : self;
}