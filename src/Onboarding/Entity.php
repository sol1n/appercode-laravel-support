<?php

namespace Appercode\Onboarding;

use GuzzleHttp\Exception\BadResponseException;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\EntityInterface as OnboardingEntity;

use Appercode\Exceptions\Onboarding\CreateException;
use Appercode\Exceptions\Onboarding\DeleteException;
use Appercode\Exceptions\Onboarding\RecieveException;
use Appercode\Exceptions\Onboarding\SaveException;

use Appercode\Traits\AppercodeRequest;

use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class Entity implements OnboardingEntity
{
    use AppercodeRequest;

    protected $backend;

    public $id;
    public $createdAt;
    public $updatedAt;
    public $updatedBy;
    public $isDeleted;

    abstract protected function toJson(): array;
    abstract protected static function methods(Backend $backend, string $name, array $data = []);

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
        $this->createdAt = new Carbon($data['createdAt']);
        $this->updatedAt = new Carbon($data['updatedAt']);

        $this->updatedBy = $data['updatedBy'];
        $this->isDeleted = (bool) $data['isDeleted'];

        $this->backend = $backend;

        return $this;
    }

    /**
     * Creates new entity and returns it instance
     * @param  array   $fields
     * @param  Appercode\Contracts\Backend $backend
     * @return Appercode\Contracts\Onboarding\EntityInterface
     * @throws Appercode\Exceptions\Onboarding\CreateException
     */
    public static function create(array $fields, Backend $backend): OnboardingEntity
    {
        try {
            $method = static::methods($backend, 'create');

            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new static($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, $fields);
        }
    }

    /**
     * Returns entity instance by id
     * @param  string   id
     * @param  Appercode\Contracts\Backend $backend
     * @return Appercode\Contracts\Onboarding\EntityInterface
     * @throws Appercode\Exceptions\Onboarding\RecieveException
     */
    public static function find(string $id, Backend $backend): OnboardingEntity
    {
        try {
            $method = static::methods($backend, 'get', [
                'id' => $id
            ]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new static($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new RecieveException($message, $code, $e, [
                'id' => $id
            ]);
        }
    }

    /**
     * Returns entities count
     * @param  Appercode\Contracts\Backend $backend
     * @param  array   $filter
     * @return int
     * @throws Appercode\Exceptions\Onboarding\RecieveException
     */
    public static function count(Backend $backend, array $filter = []): int
    {
        try {
            $method = static::methods($backend, 'count');

            $fields = [
                'take' => 0
            ];

            if (!empty($filter)) {
                $fields['where'] = $filter;
            }

            return self::countRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new RecieveException($message, $code, $e, [
                'count' => true,
                'filter' => $filter
            ]);
        }
    }

    /**
     * Lists entities with order, filter
     * @param  Appercode\Contracts\Backend $backend
     * @param  array   $filter
     * @return Illuminate\Support\Collection
     * @throws Appercode\Exceptions\Onboarding\RecieveException
     */
    public static function list(Backend $backend, array $filter = []): Collection
    {
        try {
            $method = static::methods($backend, 'list');

            return collect(self::jsonRequest([
                'method' => $method['type'],
                'json' => (object) $filter,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]))->map(function ($data) use ($backend) {
                return new static($data, $backend);
            });
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new RecieveException($message, $code, $e, [
                'count' => false,
                'filter' => $filter
            ]);
        }
    }

    /**
     * Save fields for provided entity id without fetching
     * @param  string  $id
     * @param  array   $fields
     * @param  Appercode\Contracts\Backend $backend
     * @throws Appercode\Exceptions\Onboarding\SaveException
     * @return void
     */
    public static function update(string $id, array $fields, Backend $backend)
    {
        try {
            $method = static::methods($backend, 'update', [
                'id' => $id
            ]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new SaveException($message, $code, $e);
        }
    }

    /**
     * Static method for removing entity
     * @param  string  $id
     * @param  Appercode\Contracts\Backend $backend
     * @throws Appercode\Exceptions\Onboarding\DeleteException
     * @return void
     */
    public static function remove(string $id, Backend $backend)
    {
        try {
            $method = static::methods($backend, 'delete', [
                'id' => $id
            ]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new DeleteException($message, $code, $e);
        }
    }

    /**
     * Updates entity instance fields to appercode backend
     * @return Appercode\Contracts\Onboarding\EntityInterface
     * @throws Appercode\Exceptions\Onboarding\SaveException
     */
    public function save(): OnboardingEntity
    {
        try {
            $method = static::methods($this->backend, 'update', [
                'id' => $this->id
            ]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $this->toJson(),
                'headers' => ['X-Appercode-Session-Token' => $this->backend->token()],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new SaveException($message, $code, $e, $this);
        }

        return $this;
    }

    /**
     * Removes entity and returns instance
     * @return Appercode\Contracts\Onboarding\EntityInterface
     * @throws Appercode\Exceptions\Onboarding\DeleteException
     */
    public function delete(): OnboardingEntity
    {
        try {
            $method = static::methods($this->backend, 'delete', [
                'id' => $this->id
            ]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => ['X-Appercode-Session-Token' => $this->backend->token()],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new DeleteException($message, $code, $e, $this);
        }

        return $this;
    }
}
