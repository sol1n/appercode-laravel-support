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

    public static function count(string $schemaName, Backend $backend, $query = [])
    {
        $method = $backend->methods('elements_count', ['schema' => $schemaName]);

        return self::countRequest([
            'json' => $query,
            'url' => $method['url'],
            'method' => $method['type'],
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
        ]);
    }

    public static function create(string $schemaName, array $fields, Backend $backend): Element
    {
        $method = $backend->methods('elements_create', ['schema' => $schemaName]);

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
}
