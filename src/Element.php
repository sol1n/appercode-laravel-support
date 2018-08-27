<?php

namespace Appercode;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

use Appercode\Schema;
use Appercode\Backend;
use Appercode\Traits\AppercodeRequest;
use Appercode\Traits\SchemaName;

use Appercode\Exceptions\Element\ReceiveException;

use Appercode\Contracts\ElementContract;

class Element implements ElementContract
{
    use AppercodeRequest, SchemaName;

    private $backend;
    private $schema;

    public $id;
    public $createdAt;
    public $updatedAt;
    public $ownerId;
    public $schemaName;
    public $isPublished;

    public $fields;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/query?count=true'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/query'
                ];
            case 'find':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/' . $data['id']
                ];
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema']
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/' . $data['id']
                ];
            case 'bulk-update':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/batch'
                ];
            case 'bulk-query':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/batch/query'
                ];
            case 'bulk-delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/batch'
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    private function innerFields()
    {
        return [
            'id',
            'createdAt',
            'updatedAt',
            'ownerId'
        ];
    }

    private function isInnerField($name): bool
    {
        return in_array($name, $this->innerFields());
    }

    public function __construct(array $data, Backend $backend, $schema = null)
    {
        $this->id = $data['id'];
        $this->createdAt = new Carbon($data['createdAt']);
        $this->updatedAt = new Carbon($data['updatedAt']);
        $this->ownerId = $data['ownerId'];
        $this->fields = [];

        foreach ($data as $index => $value) {
            if (! $this->isInnerField($index)) {
                $this->fields[$index] = $value;
            }
        }

        $this->backend = $backend;
        if (!is_null($schema) and $schema instanceof Schema) {
            $this->schema = $schema;
            $this->schemaName = $schema->id;
        }

        if (!is_null($schema) and is_string($schema)) {
            $this->schemaName = $schema;
        }

        return $this;
    }

    public static function count($schema, Backend $backend, $query = []): int
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'count', ['schema' => $schemaName]);

        return self::countRequest([
            'json' => count($query) ? $query : (object)[],
            'url' => $method['url'],
            'method' => $method['type'],
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
        ]);
    }

    /**
     * Creates new element in selected schema
     * @param  Appercode/Schema|string  $schema The schema in which the element will be created
     * @param  array   $fields     [description]
     * @param  Backend $backend    [description]
     * @return [type]              [description]
     */
    public static function create($schema, array $fields, Backend $backend): ElementContract
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'create', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => $fields,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);

        return new Element($json, $backend, $schema);
    }

    public static function update($schema, string $id, array $fields, Backend $backend)
    {
    }

    public function save(): ElementContract
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'create', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => $fields,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);
    }

    /**
     * Deletes current element
     * @return Appercode\Element removed element instance
     */
    public function delete(): ElementContract
    {
        $method = self::methods($this->backend, 'delete', ['schema' => $this->schemaName, 'id' => $this->id]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'headers' => [
                'X-Appercode-Session-Token' => $this->backend->token()
            ],
            'url' => $method['url'],
        ]);

        return $this;
    }

    /**
     * Returns collection elements from schema with filter
     * @param  Appercode\Schema|string  $schema
     * @param  Appercode\Backend $backend
     * @param  array|null  $filter
     * @return Illuminate\Support\Collection
     */
    public static function list($schema, Backend $backend, $filter = null): Collection
    {
        $result = new Collection;

        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'list', ['schema' => $schemaName]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $filter ?? (object) [],
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['schema' => $schemaName]);
        }

        foreach ($json as $element) {
            $result->push(new Element($element, $backend, $schema));
        }

        return $result;
    }

    /**
     * Returns single element from collection
     * @param  Appercode\Schema  $schema
     * @param  string  $id
     * @param  Appercode\Backend $backend
     * @return Appercode\Element
     * @throws Appercode\Exceptions\Element\ReceiveException
     */
    public static function find($schema, string $id, Backend $backend): ElementContract
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'find', ['schema' => $schemaName, 'id' => $id]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $filter ?? (object) [],
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['schema' => $schemaName, 'id' => $id]);
        }

        return new Element($json, $backend, $schema);
    }

    /**
     * Elements bulk update method
     * @param  array   $ids
     * @param  array   $changes
     * @param  Appercode\Schema|string  $schema
     * @param  Backend $backend
     * @return boolean
     */
    public static function bulkUpdate($schema, array $ids, array $changes, Backend $backend)
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'bulk-update', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => [
                'ids' => $ids,
                'changes' => $changes
            ],
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);

        return true;
    }

    /**
     * Returns results of bulk queries to collection
     * @param  Appercode\Schema|string  $schema
     * @param  array   $queries array of objects with keys: count, where, include, order, skip, take
     * @param  Appercode\Backend $backend
     * @return Illuminate\Support\Collection collection of objects with keys: count, list
     */
    public static function bulk($schema, array $queries, Backend $backend): Collection
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'bulk-query', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => $queries,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);

        $results = new Collection;
        foreach ($json as $one) {
            $part = new Collection();
            if (isset($one['list']) && is_array($one['list']) && count($one['list'])) {
                foreach ($one['list'] as $element) {
                    $part->push(new Element($element, $backend, $schema));
                }
            }
            $results->push([
                'count' => isset($one['count']) ? (int) $one['count'] : null,
                'list' => $part
            ]);
        }

        return $results;
    }

    /**
     * Executes batch elements delete request
     * @param  Appercode\Schema|string  $schema
     * @param  array   $ids     array of elements id
     * @param  Appercode\Backend $backend
     * @return mixed
     */
    public static function bulkDelete($schema, array $ids, Backend $backend)
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'bulk-delete', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => $ids,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);

        return true;
    }
}
