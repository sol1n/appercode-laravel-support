<?php

namespace Appercode\Exceptions\Element;

use Exception;

class ViewsSendException extends Exception
{
    public $events;

    public function __construct($message = "", $code = 0, Exception $previous = null, $events = null)
    {
        parent::__construct($message, $code, $previous);
        $this->events = $events;
    }
}
