<?php

namespace Appercode\Traits;

use Appercode\Backend;
use Appercode\Traits\AppercodeRequest;
use GuzzleHttp\Exception\ClientException;

use Appercode\Exceptions\User\WrongCredentialsException;

trait Authenticatable
{
    private static $currentUser = null;
    
    public static function current()
    {
        return self::$currentUser;
    }

    public static function setCurrent($user)
    {
        self::$currentUser = $user;
    }

    public static function login(Backend $backend, string $username, string $password)
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

    public static function loginByToken(Backend $backend, string $token)
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


    public function regenerateToken()
    {
        return self::loginByToken($this->backend, $this->refreshToken);
    }
}
