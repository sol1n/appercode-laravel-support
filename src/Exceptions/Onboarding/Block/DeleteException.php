<?php

namespace Appercode\Exceptions\Onboarding\Block;

use Exception;

class DeleteException extends Exception
{
    public $task;

    public function __construct($message = "", $code = 0, Exception $previous = null, $task = null)
    {
        parent::__construct($message, $code, $previous);
        $this->task = $task;
    }
}
