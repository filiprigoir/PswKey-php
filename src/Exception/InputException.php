<?php
declare(strict_types=1);

namespace PswKey\Exception;

class InputException extends GlobalException {
    
    public function __construct($message) {
        parent::__construct($message);
    }
}