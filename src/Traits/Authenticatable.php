<?php

namespace Appercode\Traits;

use Appercode\Backend;
use Appercode\Traits\AppercodeRequest;
use GuzzleHttp\Exception\ClientException;

use Appercode\Exceptions\User\WrongCredentialsException;

trait Authenticatable
{
    public static function login(Backend $backend, string $username, string $password)
    {
        try {
            $json = self::jsonRequest([
                'method' => $backend->methods('login')['type'],
                'json' => [
                    'username' => $username,
                    'password' => $password
                ],
                'url' => $backend->methods('login')['url'],
            ], false);

            $user = new self;
            $user->id = $json['userId'] ?? null;
            $user->token = $json['sessionId'] ?? null;
            $user->role = $json['roleId'] ?? null;
            $user->backend = $backend;

            $backend->setUser($user);
            
            return $user;
        } catch (ClientException $e) {
            throw new WrongCredentialsException;
        }
    }
}
