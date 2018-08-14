<?php

namespace Appercode\Traits;

use Appercode\Schema;

trait SchemaName
{
    /**
     * Check parameter type and return schema name.
     * Allows provide Appercode\Schema and string schema name as one parameter
     * @param  Appercode\Schema|string $schema
     * @return string
     */
    private static function getSchemaName($schema): string
    {
        $schemaName = $schema instanceof Schema
            ? $schema->id
            : (is_string($schema)
                ? $schema
                : null);

        if (is_null($schemaName)) {
            throw new \Exception('Empty schema provided');
        }

        return $schemaName;
    }
}
