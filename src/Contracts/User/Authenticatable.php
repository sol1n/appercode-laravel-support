<?php

namespace Appercode\Contracts\User;

use Appercode\Contracts\Backend;

interface Authenticatable
{
    public static function current(): Authenticatable;
    public static function setCurrent(Authenticatable $user);
    public static function login(Backend $backend, string $username, string $password): Authenticatable;
    public static function loginByToken(Backend $backend, string $token): Authenticatable;
    public function regenerateToken(): Authenticatable;
}
