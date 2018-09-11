<?php

namespace Appercode\Exceptions\Element;

use Exception;

class DeleteException extends Exception
{
    public $fields;

    public function __construct($message = "", $code = 0, Exception $previous = null, $fields = null)
    {
        parent::__construct($message, $code, $previous);
        $this->fields = $fields;
    }
}
