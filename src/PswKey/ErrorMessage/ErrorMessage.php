<?php
declare(strict_types=1);

namespace PswKey\ErrorMessage;

/*
* Catch any exception and separate into system message (for developer) and customer message (for outside output)
*/
final class ErrorMessage {

    private function __construct() {}

    public static function create(string $exceptionStr) : Array {     

        $messages = explode("/", $exceptionStr);
        $customerMess = "Invalid input";
        if(isset($messages[1]))
            $customerMess = $messages[1];
        
        return ['systemError' => $messages[0], 'customerError' => $customerMess];
    }
}