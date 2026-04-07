<?php
declare(strict_types=1);

namespace PswKey\Exception;

class ConfigurationException extends GlobalException {
    
    public function __construct($message) {
        parent::__construct($message);
    }
}