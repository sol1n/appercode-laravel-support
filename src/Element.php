<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;
use Appercode\Traits\SchemaName;

use Appercode\Contracts\Element as ElementContract;

use Appercode\Exceptions\Element\ReceiveException;
use Appercode\Exceptions\Element\SaveException;

use Appercode\Schema;
use Appercode\Backend;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

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
    public $languages = [];

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
            case 'save':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/' . $data['id']
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

    /**
     * Returns count of elements for schema and filter
     * @param  Appercode\Schema|string  $schema
     * @param  Appercode\Backend $backend
     * @param  array   $query   filter and request params
     * @return int
     */
    public static function count($schema, Backend $backend, $query = []): int
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'count', ['schema' => $schemaName]);

        try {
            return self::countRequest([
                'json' => count($query) ? $query : (object)[],
                'url' => $method['url'],
                'method' => $method['type'],
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['schema' => $schemaName, $query => $query, 'count' => true]);
        }
    }

    /**
     * Creates new element in selected schema
     * @param  Appercode/Schema|string  $schema Schema of a new element
     * @param  array   $fields
     * @param  Appercode\Backend $backend
     * @return Appercode\Contracts\ElementContract
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

    /**
     * Static method for saving selected fields without getting model
     * @param  Appercode\Schema|string $schema
     * @param  string  $id
     * @param  array   $fields
     * @param  Appercode\Backend $backend
     * @return void
     */
    public static function update($schema, string $id, array $fields, Backend $backend): void
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'save', ['schema' => $schemaName, 'id' => $id]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new SaveException($message, $code, $e, ['schema' => $schemaName, 'id' => $id, 'fields' => $fields]);
        }
    }

    /**
     * Saves localized fields values for provided languages
     * @param  Appercode\Schema|string  $schema
     * @param  $id
     * @param  array   $languages as $language => $fieldsValues
     * @param  Appercode\Backend $backend
     * @return void
     */
    public static function updateLanguages($schema, string $id, array $languages, Backend $backend): void
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'save', ['schema' => $schemaName, 'id' => $id]);

        foreach ($languages as $language => $fields) {
            try {
                $json = self::jsonRequest([
                    'method' => $method['type'],
                    'json' => $fields,
                    'headers' => [
                        'X-Appercode-Session-Token' => $backend->token(),
                        'X-Appercode-Language' => $language
                    ],
                    'url' => $method['url'],
                ]);
            } catch (BadResponseException $e) {
                $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
                $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

                throw new SaveException($message, $code, $e, ['schema' => $schemaName, 'id' => $id, 'fields' => $fields, 'language' => $language]);
            }
        }
    }

    /**
     * Save element instance fields changes to appercode backend
     * @throws Appercode\Exceptions\Element\SaveException
     * @return Appercode\Contracts\ElementContract current element instance
     */
    public function save(): ElementContract
    {
        $method = self::methods($this->backend, 'save', ['schema' => $this->schemaName, 'id' => $this->id]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $this->fields,
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new SaveException($message, $code, $e, ['object' => $this]);
        }

        return $this;
    }

    /**
     * Loads localized fields values for provided languages
     * @param  array|string $languages
     * @return Appercode\Contracts\ElementContract
     */
    public function getLanguages($languages): ElementContract
    {
        if (is_string($languages) && $languages) {
            $languages = [$languages];
        }

        if (is_array($languages)) {
            foreach ($languages as $language) {
                $method = self::methods($this->backend, 'find', [
                    'schema' => $this->schemaName,
                    'id' => $this->id
                ]);
                $json = self::jsonRequest([
                    'method' => $method['type'],
                    'headers' => [
                        'X-Appercode-Session-Token' => $this->backend->token(),
                        'X-Appercode-Language' => $language
                    ],
                    'url' => $method['url'],
                ]);

                $languageFields = [];
                foreach ($json as $key => $field) {
                    if (! $this->isInnerField($key)) {
                        $languageFields[$key] = $field;
                    }
                }

                $this->languages[$language] = $languageFields;
            }
            return $this;
        }

        throw new \InvalidArgumentException('Languages parameter should be array or string type');
    }

    /**
     * Deletes current element
     * @return Appercode\Element removed element instance
     */
    public function delete(): ElementContract
    {
        $method = self::methods($this->backend, 'delete', ['schema' => $this->schemaName, 'id' => $this->id]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new DeleteException($message, $code, $e, ['schema' => $this->schemaName]);
        }

        return $this;
    }

    /**
     * Returns collection elements from schema with filter
     * @param  Appercode\Schema|string  $schema
     * @param  Appercode\Backend $backend
     * @param  array|null  $filter
     * @param  array|string  $languages
     * @return Illuminate\Support\Collection
     */
    public static function list($schema, Backend $backend, $filter = null, $languages = []): Collection
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

        $languagesFields = [];
        if (count($languages) && count($json)) {
            $ids = collect($json)->map(function ($item) {
                return $item['id'];
            });

            foreach ($languages as $language) {
                $languagesFields[$language] = collect(self::jsonRequest([
                    'method' => $method['type'],
                    'json' => [
                        'take' => -1,
                        'where' => [
                            'id' => [
                                '$in' => $ids
                            ]
                        ]
                    ],
                    'headers' => [
                        'X-Appercode-Session-Token' => $backend->token(),
                        'X-Appercode-Language' => $language
                    ],
                    'url' => $method['url'],
                ]))->mapWithKeys(function ($item) {
                    return [$item['id'] => $item];
                });
            }
        }

        foreach ($json as $jsonElement) {
            $element = new Element($jsonElement, $backend, $schema);
            foreach ($languagesFields as $language => $languageFields) {
                if (isset($languageFields[$element->id])) {
                    $element->languages[$language] = $languageFields[$element->id];
                }
            }
            $result->push($element);
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
     * @return void
     */
    public static function bulkUpdate($schema, array $ids, array $changes, Backend $backend): void
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
     * @return void
     */
    public static function bulkDelete($schema, array $ids, Backend $backend): void
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($backend, 'bulk-delete', ['schema' => $schemaName]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $ids,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new DeleteException($message, $code, $e, ['schema' => $schemaName]);
        }
    }
}
