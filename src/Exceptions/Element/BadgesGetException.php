<?php

namespace Appercode\Exceptions\Element;

use Exception;

class BadgesGetException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
