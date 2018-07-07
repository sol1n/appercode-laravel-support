<?php

namespace Appercode;

use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;

use Appercode\Backend;
use Appercode\Traits\AppercodeRequest;
use Appercode\Helpers\Schema\ViewData as ViewDataHelper;
use Appercode\Exceptions\Schema\CreationException;

class Schema
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $title;
    public $fields;
    public $isDeferredDeletion;
    public $isLogged;
    /**
     * Used as a filter for get user relation
     * @var array
     */
    public $filterUsers;
    public $viewDataHelper;

    const COLLECTION_TYPES = [
        'areaCatalogItem',
        'generalCatalogItem',
        'eventCatalogItem',
        'feedbackMessage',
        'htmlPage',
        'newsCatalogItem',
        'photoCatalogItem',
        'tag',
        'userProfile',
        'videoCatalogItem'
    ];

    const PARENT_FIELD_NAME = 'parentId';

    private static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/schemas'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/schemas/' . $data['schema']
                ];
            case 'get':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/schemas/' . $data['schema']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
        $this->title = $data['title'] ? $data['title'] : $data['id'];
        $this->fields = $data['fields'];
        $this->createdAt = new Carbon($data['createdAt']);
        $this->updatedAt = new Carbon($data['updatedAt']);
        $this->isDeferredDeletion = $data['isDeferredDeletion'];
        $this->isLogged = $data['isLogged'];
        $this->viewData = is_array($data['viewData']) ? $data['viewData'] : json_decode($data['viewData']);

        if ($this->fields) {
            foreach ($this->fields as &$field) {
                if (mb_strpos($field['type'], '[') !== false) {
                    $field['multiple'] = true;
                    $field['type'] = preg_replace('/\[(.+)\]/', '\1', $field['type']);
                } else {
                    $field['multiple'] = false;
                }
            }
        }
        
        $this->viewDataHelper = new ViewDataHelper($this);
        $this->backend = $backend;

        return $this;
    }

    private function prepareField(array $field): array
    {
        $field['localized'] = $field['localized'] == 'true';
        $field['multiple'] = isset($field['multiple']) && $field['multiple'] == 'true';
        $field['title'] = (String) $field['title'];

        if (isset($field['deleted'])) {
            unset($field['deleted']);
        }
        if ($field['multiple']) {
            $field['type'] = "[" . $field['type'] . "]";
        }
        return $field;
    }

    private function getChanges(array $data): array
    {
        $changes = [];

        if (isset($data['viewData'])) {
            $viewData = $data['viewData'];
            unset($data['viewData']);

            $this->viewData = $this->viewData ? (array) $this->viewData : [];

            foreach ($viewData as $key => $field) {
                $this->viewData[$key] = $field;
            }

            $changes[] = [
                'action' => 'Change',
                'key' => $this->id . '.viewData',
                'value' => $this->viewData,
            ];
        }

        if (isset($data['deletedFields'])) {
            $deletedFields = $data['deletedFields'];
            unset($data['deletedFields']);

            foreach ($deletedFields as $fieldName => $fieldData) {
                $changes[] = [
                    'action' => 'Delete',
                    'key' => $this->id . '.' . $fieldName,
                ];
            }
        }

        if (isset($data['fields'])) {
            $fields = $data['fields'];
            unset($data['fields']);
            foreach ($fields as $fieldName => &$fieldData) {
                $field = [];
                $fieldData = $this->prepareField($fieldData);

                foreach ($this->fields as $key => $value) {
                    if ($fieldName == $value['name']) {
                        $field = $value;
                    }
                }

                if ($field and $field['multiple']) {
                    $field['type'] = '[' . $field['type'] . ']';
                }

                foreach ($fieldData as $key => $value) {
                    if ($field && $value != $field[$key]) {
                        if ($key == 'name') {
                            $changes[] = [
                                'action' => 'Change',
                                'key' => $this->id . '.' . $fieldName ,
                                'value' => $value,
                            ];
                        } elseif ($key == 'multiple') {
                            $newValue = $value ? '[' . $field['type'] . ']' : $field['type'];
                            $newFieldDate = $fieldData;
                            unset($newFieldDate['multiple']);
                            $newFieldDate['type'] = $newValue;
                            $changes[] = [
                                'action' => 'Delete',
                                'key' => $this->id . '.' . $fieldName ,
                            ];
                            $changes[] = [
                                'action' => 'New',
                                'key' => $this->id,
                                'value' => $newFieldDate
                            ];
                        } elseif ($key == 'type') {
                            $changes[] = [
                                'action' => 'Delete',
                                'key' => $this->id . '.' . $fieldName ,
                            ];
                            $changes[] = [
                                'action' => 'New',
                                'key' => $this->id,
                                'value' => $fieldData
                            ];
                        } else {
                            $changes[] = [
                                'action' => 'Change',
                                'key' => $this->id . '.' . $fieldName . '.' . $key,
                                'value' => $value,
                            ];
                        }
                    }
                }
            }
        }

        if (isset($data['newFields'])) {
            $newFields = $data['newFields'];
            unset($data['newFields']);

            foreach ($newFields as $fieldName => $fieldData) {
                $changes[] = [
                    'action' => 'New',
                    'key' => $this->id,
                    'value' => $this->prepareField($fieldData)
                ];
            }
        }

        foreach ($data as $name => $value) {
            if ($value != $this->{$name}) {
                $changes[] = [
                    'action' => 'Change',
                    'key' => $this->id . '.' . $name,
                    'value' => $value
                ];
            }
        }

        return $changes;
    }

    /**
     * Сreates a new scheme
     * @param  Array   $data must contain items:
     *
     * id: required unique string
     * title: optional string
     * isLogged: optional boolean
     * isDeferredDeletion: optional boolean
     * viewData: optional array
     * fields: optional array of fields descriptions like
     *
     * [
     *     'name' => 'fieldName',
     *     'type' => 'fieldType',
     *     'title' => 'fieldTitle',
     *     'multiple' => false,
     *     'localized' => false
     * ]
     *
     * @param  Appercode\Backend $backend
     * @throws Appercode\Exceptions\Schema\CreationException
     * @return Appercode\Schema
     */
    public static function create(array $data, Backend $backend): Schema
    {
        $fields = [
            "id" => (string) $data['id'],
            "title" => (string) $data['title'] ?? '',
            "isLogged" => (bool) ($data['isLogged'] ?? false),
            "isDeferredDeletion" => (bool) ($data['isDeferredDeletion'] ?? false),
            "viewData" => $data['viewData'] ?? [],
            "fields" => []
        ];

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $field) {
                $type = (string) $field['type'];
                if (isset($field['multiple']) and $field['multiple'] == 'true') {
                    $type = "[$type]";
                }
                $fields['fields'][] = [
                    "localized" => (bool) ($field['localized'] ?? false),
                    "name" => (string) $field['name'],
                    "type" => $type,
                    "title" => (string) ($field['title'] ?? '')
                ];
            }
        }

        try {
            $method = self::methods($backend, 'create');

            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new Schema($json, $backend);
        } catch (ClientException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            if ($code == 409) {
                throw new CreationException("Schema id \"{$fields['id']}\" is not unique", $code, $e, $data);
            } else {
                throw new CreationException($message, $code, $e, $data);
            }
        }
    }

    /**
     * Receives single schema instance
     * @param  string  $schemaName
     * @param  Appercode\Backend $backend
     * @return Appercode\Schema
     */
    public static function get(string $schemaName, Backend $backend): Schema
    {
        $method = self::methods($backend, 'get', ['schema' => $schemaName]);

        $json = self::jsonRequest([
            'method' => $method['type'],
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
            'url' => $method['url']
        ]);

        return new Schema($json, $backend);
    }

    /**
     * Deletes current schema
     * @return Appercode\Schema removed schema instance
     */
    public function delete(): Schema
    {
        $method = self::methods($this->backend, 'delete', ['schema' => $this->id]);

        self::request([
            'method' => $method['type'],
            'headers' => ['X-Appercode-Session-Token' => $this->backend->token()],
            'url' => $method['url'],
        ]);

        return $this;
    }
}
