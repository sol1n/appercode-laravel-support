<?php

namespace Appercode\Exceptions\Element;

use Exception;

class ViewsGetException extends Exception
{
    public $elementIds;

    public function __construct($message = "", $code = 0, Exception $previous = null, $elementIds = [])
    {
        parent::__construct($message, $code, $previous);
        $this->elementIds = $elementIds;
    }
}
