<?php
declare(strict_types=1);

namespace PswKey\ErrorMessage;

final class ClientMessage {

    public const INVALID_DEFAULT = 'Unexpected error occurred';
    public const INVALID_INPUT = "Provided value is invalid";
    public const INVALID_EMPTY = "Provided value is null or empty";
    public const VALIDATION_FAILED = "Provided value is incorrect";

    private function __construct() {}
}