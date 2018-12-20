<?php

namespace Appercode\Contracts;

use Appercode\Contracts\Backend;

interface Schema
{
    public static function create(array $data, Backend $backend): Schema;
    public static function find(string $schemaName, Backend $backend): Schema;
    public function delete(): Schema;
}
