<?php

namespace Appercode;

use Carbon\Carbon;

use Appercode\Backend;
use Appercode\Schema;
use Appercode\Traits\AppercodeRequest;

class Element
{
    use AppercodeRequest;

    private $backend;
    private $schema;

    public $id;
    public $createdAt;
    public $updatedAt;
    public $ownerId;

    public $fields;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema'] . '/query?count=true'
                ];
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/objects/' . $data['schema']
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backendbackend->project . '/objects/' . $data['schema'] . '/' . $data['id']
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
        }

        return $this;
    }

    public static function count(string $schemaName, Backend $backend, $query = []): int
    {
        $method = self::methods($backend, 'count', ['schema' => $schemaName]);

        return self::countRequest([
            'json' => $query,
            'url' => $method['url'],
            'method' => $method['type'],
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
        ]);
    }

    /**
     * Creates new element in selected schema
     * @param  string  $schemaName The schema in which the element will be created
     * @param  array   $fields     [description]
     * @param  Backend $backend    [description]
     * @return [type]              [description]
     */
    public static function create(string $schemaName, array $fields, Backend $backend): Element
    {
        $method = self::methods($backend, 'create', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => $fields,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);

        return new Element($json, $backend);
    }

    /**
     * Deletes current element
     * @return Appercode\Element removed element instance
     */
    public function delete(): Element
    {
        $method = self::methods($backend, 'delete', ['schema' => $schemaName, 'id' => $this->id]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => $fields,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);

        return $this;
    }
}
