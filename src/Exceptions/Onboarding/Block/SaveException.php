<?php

namespace Appercode\Exceptions\Onboarding\Block;

use Exception;

class SaveException extends Exception
{
    public $block;

    public function __construct($message = "", $code = 0, Exception $previous = null, $block = null)
    {
        parent::__construct($message, $code, $previous);
        $this->block = $block;
    }
}
