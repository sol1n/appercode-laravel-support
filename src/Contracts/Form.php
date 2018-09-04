<?php

namespace Appercode\Contracts;

use Appercode\Backend;
use Illuminate\Support\Collection;

interface Form
{
    public static function create(array $fields, Backend $backend): Form;
    public static function list(Backend $backend, $filter = null): Collection;

    public function delete(): Form;
}
