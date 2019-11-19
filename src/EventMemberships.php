<?php

namespace Appercode;

use Appercode\Backend;
use Appercode\Traits\AppercodeRequest;

class EventMemberships
{
    use AppercodeRequest;

    public $backend;

    private static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/EventMemberships'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/EventMemberships/query'
                ];
            case 'deleteBatch':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/EventMemberships/batch'
                ];
            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(Backend $backend, array $data)
    {
        $this->id = $data['id'] ?? null;

        return $this;
    }

    public static function create(Backend $backend, array $fields)
    {
        $method = self::methods($backend, 'create');

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => (object) $fields,
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
            'url' => $method['url']
        ]);

        return new EventMemberships($backend, $json);
    }

    public static function list(Backend $backend, array $filter = [])
    {
        $method = self::methods($backend, 'list');

        $json = self::jsonRequest([
            'method' => $method['type'],
            'json' => (object) $filter,
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
            'url' => $method['url']
        ]);

        return collect($json)->map(function ($fields) use ($backend) {
            return new EventMemberships($backend, $fields);
        });
    }

    public static function remove(Backend $backend, array $ids)
    {
        $method = self::methods($backend, 'deleteBatch');

        self::request([
            'method' => $method['type'],
            'json' => $ids,
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
            'url' => $method['url']
        ]);
    }
}
