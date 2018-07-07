<?php

namespace Appercode;

use Appercode\Traits\Authenticatable;
use Appercode\Traits\AppercodeRequest;

class User
{
    use Authenticatable, AppercodeRequest;

    private static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'login':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/login'
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }
}
