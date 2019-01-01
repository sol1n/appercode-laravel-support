<?php

namespace Appercode\Exceptions\Onboarding;

use Exception;

class DeleteException extends Exception
{
    public $entity;

    public function __construct($message = "", $code = 0, Exception $previous = null, $entity = null)
    {
        parent::__construct($message, $code, $previous);
        $this->entity = $entity;
    }
}
