<?php

namespace Appercode\Exceptions\Backend;

use Exception;
use Throwable;

class BackendNotExists extends Exception
{
    public $backendName;

    public function __construct(string $backendName, string $message = "", int $code = 0, Throwable $previous = null)
    {
        $this->backendName = $backendName;
        parent::__construct($message, $code, $previous);
    }
}
