<?php
declare(strict_types=1);

namespace PswKey\Exception;

class BaseException extends GlobalException {
    
    public function __construct($message) {
        parent::__construct($message);
    }
}