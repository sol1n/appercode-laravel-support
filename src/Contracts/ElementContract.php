<?php

namespace Appercode\Contracts;

use Appercode\Backend;
use Illuminate\Support\Collection;

interface ElementContract
{
    /**
     * Static methods
     */
    public static function count($schema, Backend $backend, $query = []): int;
    public static function create($schema, array $fields, Backend $backend): ElementContract;
    public static function find($schema, string $id, Backend $backend): ElementContract;
    public static function list($schema, Backend $backend, $filter = null, $languages = []): Collection;
    public static function update($schema, string $id, array $fields, Backend $backend);
    public static function updateLanguages($schema, string $id, array $languages, Backend $backend);

    /**
     * Bulk methods
     */
    public static function bulk($schema, array $queries, Backend $backend): Collection;
    public static function bulkUpdate($schema, array $ids, array $changes, Backend $backend);
    public static function bulkDelete($schema, array $ids, Backend $backend);

    /**
     * Non-static methods
     */
    public function save(): ElementContract;
    public function getLanguages($languages): ElementContract;
    public function delete(): ElementContract;
}
