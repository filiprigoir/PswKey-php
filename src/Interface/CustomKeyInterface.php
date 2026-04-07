<?php
declare(strict_types=1);

namespace PswKey\Interface;

interface CustomKeyInterface {
    public function setCustomKey(string $seedPhrase) : self;
    public function resetCustomKey() : self;
}