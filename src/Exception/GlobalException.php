<?php
declare(strict_types=1);

namespace PswKey\Exception;

abstract class GlobalException extends \Exception {
    
    public function __construct($message) {
        parent::__construct($message);
    }
}