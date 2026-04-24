<?php
declare(strict_types=1);

namespace PswKey\ErrorMessage;

final class ErrorMessage {

    private function __construct() {}

    public static function create(string $exceptionStr) : Array {     

        $messages = explode("/", $exceptionStr);
        $clientMess = ClientMessage::INVALID_DEFAULT;
        if(isset($messages[1]))
            $clientMess = $messages[1];
        
        return ['internal' => $messages[0], 'client' => $clientMess];
    }
}