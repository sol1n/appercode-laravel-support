<?php

namespace Appercode\Contracts;

use Appercode\Backend;
use Illuminate\Support\Collection;

interface FormResponse
{
    public static function create(array $fields, string $formId, Backend $backend): FormResponse;
    public static function list(Backend $backend, array $filter = []): Collection;
    public static function count(Backend $backend, array $filter = []): int;

    public function delete(): FormResponse;
}
