<?php

namespace Appercode\Exceptions\FormReport;

use Exception;

class ReceiveException extends Exception
{
    public $fields;

    public function __construct($message = "", $code = 0, Exception $previous = null, $fields = null)
    {
        parent::__construct($message, $code, $previous);
        $this->fields = $fields;
    }
}
