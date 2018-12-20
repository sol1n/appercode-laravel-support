<?php

namespace Appercode\Contracts;

use Appercode\Contracts\User\Authenticatable;

interface Backend
{
    public function setServer(string $server): Backend;
    public function setProject(string $project): Backend;
    public function setUser(Authenticatable $user): Backend;
    public function token();
}
