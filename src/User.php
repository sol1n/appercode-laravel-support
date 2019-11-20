<?php

namespace Appercode;

use Appercode\Backend;
use Appercode\Traits\Authenticatable;
use Appercode\Traits\AppercodeRequest;

class User
{
    use Authenticatable, AppercodeRequest;

    public $backend;

    private static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'login':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/login'
                ];
            case 'loginByToken':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/login/byToken'
                ];
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/users'
                ];
            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(Backend $backend, array $data)
    {
        $this->id = $data['userId'] ?? $data['id'] ?? null;
        $this->token = $data['sessionId'] ?? null;
        $this->refreshToken = $data['refreshToken'] ?? null;
        $this->role = $data['roleId'] ?? null;
        $this->backend = $backend;

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

        return new User($backend, $json);
    }
}
