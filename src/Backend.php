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

    private $project;
    private $server;
    private $user;

    public function methods(string $name, array $data = [])
    {
        switch ($name) {
        case 'login':
          return [
          'type' => 'POST',
          'url' => $this->server . $this->project . '/login'
        ];
        case 'elements_count':
          return [
          'type' => 'POST',
          'url' => $this->server . $this->project . '/objects/' . $data['schema'] . '/query?count=true'
        ];
        case 'elements_create':
          return [
          'type' => 'POST',
          'url' => $this->server . $this->project . '/objects/' . $data['schema']
        ];
        case 'schema_create':
          return [
          'type' => 'POST',
          'url' => $this->server . $this->project . '/schemas'
        ];
        case 'schema_delete':
          return [
          'type' => 'DELETE',
          'url' => $this->server . $this->project . '/schemas/' . $data['schema']
        ];
        case 'schema_get':
          return [
          'type' => 'GET',
          'url' => $this->server . $this->project . '/schemas/' . $data['schema']
        ];
        
        default:
          throw new \Exception('Can`t find method ' . $name);
      }

        return $methods[$name] ?? null;
    }

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
