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

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(Backend $backend, array $data)
    {
        $this->id = $data['userId'] ?? null;
        $this->token = $data['sessionId'] ?? null;
        $this->refreshToken = $data['refreshToken'] ?? null;
        $this->role = $data['roleId'] ?? null;
        $this->backend = $backend;

        return $this;
    }
}
