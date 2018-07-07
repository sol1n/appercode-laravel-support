<?php

namespace Appercode;

use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Cache;

use Appercode\User;
use Appercode\Exceptions\Backend\BackendNotExists;
use Appercode\Exceptions\Backend\BackendNotSelected;
use Appercode\Exceptions\Backend\BackendNoServerProvided;
use Appercode\Exceptions\Backend\LogoutException;
use Appercode\Traits\AppercodeRequest;

class Backend
{
    const CHECK_CACHE_LIFETIME = 10;

    use AppercodeRequest;

    public $project;
    public $server;
    public $user;

    public function setServer(string $server)
    {
        $this->server = $server;
        return $this;
    }

    public function setProject(string $project)
    {
        $this->project = $project;
        return $this;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function token()
    {
        return $this->user->token ?? null;
    }

    private function check()
    {
        if (env('APPERCODE_ENABLE_CACHING') == 1 && Cache::get('backend-exists-' . $this->code)) {
            return true;
        }

        try {
            $response = self::request([
              'method' => 'GET',
              'url' => $this->url . 'app/appropriateConfiguration'
          ])->getBody()->getContents();

            if ($response !== 'null' && !is_array(json_decode($response, 1))) {
                throw new BackendNotExists($this->code);
            }
        } catch (ServerException $e) {
            throw new BackendNotExists($this->code);
        }

        if (env('APPERCODE_ENABLE_CACHING')) {
            Cache::put('backend-exists-' . $this->code, 1, self::CHECK_CACHE_LIFETIME);
        }

        return true;
    }

    public function __construct(string $project = '', string $server = '')
    {
        if ($project) {
            $this->setProject($project);
        } else {
            $this->setProject(config('appercode.project'));
        }

        if ($server) {
            $this->setServer($server);
        } else {
            $this->setServer(config('appercode.server'));
        }

        return $this;
    }
}
