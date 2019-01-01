<?php

namespace Appercode\Contracts\Onboarding;

use Appercode\Contracts\Backend;
use Illuminate\Support\Collection;

interface EntityInterface
{
    /**
     * Static methods
     */
    public static function create(array $fields, Backend $backend): EntityInterface;
    public static function find(string $id, Backend $backend): EntityInterface;
    public static function count(Backend $backend, array $filter = []): int;
    public static function list(Backend $backend, array $filter = []): Collection;
    public static function update(string $id, array $fields, Backend $backend);
    public static function remove(string $id, Backend $backend);

    public function delete(): EntityInterface;
    public function save(): EntityInterface;
}
