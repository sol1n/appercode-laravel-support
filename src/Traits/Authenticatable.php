<?php

namespace Appercode\Traits;

use Appercode\Traits\AppercodeRequest;
use GuzzleHttp\Exception\ClientException;

use Appercode\Exceptions\User\WrongCredentialsException;

use Appercode\Contracts\Backend;
use Appercode\Contracts\User\Authenticatable as AuthenticatableContract;

trait Authenticatable
{
    private static $currentUser = null;
    
    public static function current(): AuthenticatableContract
    {
        return self::$currentUser;
    }

    public static function setCurrent(AuthenticatableContract $user)
    {
        self::$currentUser = $user;
    }

    public static function login(Backend $backend, string $username, string $password): AuthenticatableContract
    {
        try {
            $json = self::jsonRequest([
                'method' => self::methods($backend, 'login')['type'],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                    'generateRefreshToken' => true
                ],
                'url' => self::methods($backend, 'login')['url'],
            ], false);

            $user = new self($backend, $json);

            $backend->setUser($user);
            self::$currentUser = $user;
            
            return $user;
        } catch (ClientException $e) {
            throw new WrongCredentialsException;
        }
    }

    public static function loginByToken(Backend $backend, string $token): AuthenticatableContract
    {
        $json = self::jsonRequest([
            'method' => self::methods($backend, 'loginByToken')['type'],
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '"' . $token . '"',
            'url' => self::methods($backend, 'loginByToken')['url'],
        ], false);

        $user = new self($backend, $json);
        $backend->setUser($user);
        self::$currentUser = $user;
        
        return $user;
    }


    public function regenerateToken(): AuthenticatableContract
    {
        return self::loginByToken($this->backend, $this->refreshToken);
    }
}
