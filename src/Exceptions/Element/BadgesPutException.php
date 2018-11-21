<?php

namespace Appercode\Exceptions\Element;

use Exception;

class BadgesPutException extends Exception
{
    public $budgets;

    public function __construct($message = "", $code = 0, Exception $previous = null, $budgets = null)
    {
        parent::__construct($message, $code, $previous);
        $this->budgets = $budgets;
    }
}
