<?php

namespace Appercode\Contracts;

use Appercode\Contracts\Backend;
use Illuminate\Support\Collection;

interface FormReport
{
    public static function create(Backend $backend, string $formId, array $controlsIds): FormReport;
    public function results();
}
