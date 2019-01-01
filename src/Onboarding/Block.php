<?php

namespace Appercode\Onboarding;

use GuzzleHttp\Exception\BadResponseException;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Block as BlockContract;

use Appercode\Exceptions\Onboarding\Block\CreateException;
use Appercode\Exceptions\Onboarding\Block\DeleteException;
use Appercode\Exceptions\Onboarding\Block\RecieveException;
use Appercode\Exceptions\Onboarding\Block\SaveException;

use Appercode\Traits\AppercodeRequest;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class Block implements BlockContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $createdAt;
    public $updatedAt;
    public $updatedBy;
    public $isDeleted;

    public $title;
    public $icons;
    public $taskIds;
    public $orderIndex;

    protected static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/blocks'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/onboarding/blocks/' . $data['id']
                ];
            case 'get':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/onboarding/blocks/' . $data['id']
                ];
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/blocks/query?count=true'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/blocks/query'
                ];
            case 'update':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/onboarding/blocks/' . $data['id']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
        $this->createdAt = new Carbon($data['createdAt']);
        $this->updatedAt = new Carbon($data['updatedAt']);

        $this->updatedBy = $data['updatedBy'];
        $this->isDeleted = (bool) $data['isDeleted'];

        $this->title = $data['title'] ?? null;
        $this->icons = $data['icons'] ?? [];
        $this->taskIds = $data['taskIds'] ?? [];
        $this->orderIndex = $data['orderIndex'] ?? null;

        $this->backend = $backend;

        return $this;
    }

    /**
     * Json data for sending into appercode methods
     * @return array
     */
    public function toJson(): array
    {
        return [
            'title' => $this->title,
            'icons' => (object) $this->icons,
            'taskIds' => $this->taskIds,
            'orderIndex' => $this->orderIndex
        ];
    }

    /**
     * Creates new block
     * @param  array   $fields
     * @param  Appercode\Contracts\Backend $backend
     * @return Appercode\Contracts\Onboarding\Block
     * @throws Appercode\Exceptions\Onboarding\Block\CreateException
     */
    public static function create(array $fields, Backend $backend): BlockContract
    {
        try {
            $method = self::methods($backend, 'create');

            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new Block($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, $fields);
        }
    }

    /**
     * Returns block instance by id
     * @param  string  $id
     * @param  Appercode\Contracts\Backend $backend
     * @return Appercode\Contracts\Onboarding\Block
     * @throws Appercode\Exceptions\Onboarding\Block\RecieveException
     */
    public static function find(string $id, Backend $backend): BlockContract
    {
        try {
            $method = self::methods($backend, 'get', ['id' => $id]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new Block($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new RecieveException($message, $code, $e, [
                'id' => $id
            ]);
        }
    }

    /**
     * Returns blocks count with filter support
     * @param  Appercode\Contracts\Backend $backend
     * @param  array   $filter
     * @return int
     * @throws Appercode\Exceptions\Onboarding\Block\RecieveException
     */
    public static function count(Backend $backend, array $filter = []): int
    {
        try {
            $method = self::methods($backend, 'count');

            $fields = ['take' => 0];

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
     * Returns blocks collection
     * @param  Appercode\Contracts\Backend $backend
     * @param  array   $filter
     * @return Illuminate\Support\Collection
     * @throws Appercode\Exceptions\Onboarding\Block\RecieveException
     */
    public static function list(Backend $backend, array $filter = []): Collection
    {
        try {
            $method = self::methods($backend, 'list');

            return collect(self::jsonRequest([
                'method' => $method['type'],
                'json' => (object) $filter,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]))->map(function ($data) use ($backend) {
                return new Block($data, $backend);
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
     * Update block by id
     * @param  string  $id
     * @param  array   $fields
     * @param  Appercode\Contracts\Backend $backend
     * @return void
     * @throws Appercode\Exceptions\Onboarding\Block\SaveException
     */
    public static function update(string $id, array $fields, Backend $backend)
    {
        try {
            $method = self::methods($backend, 'update', [
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
     * Remove block by id
     * @param  string  $id
     * @param  Appercode\Contracts\Backend $backend
     * @return void
     * @throws Appercode\Exceptions\Onboarding\Block\DeleteException
     */
    public static function remove(string $id, Backend $backend)
    {
        try {
            $method = self::methods($backend, 'delete', [
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
     * Remove block instance from appercode backend
     * @return Appercode\Contracts\Onboarding\Block
     * @throws Appercode\Exceptions\Onboarding\Block\DeleteException
     */
    public function delete(): BlockContract
    {
        try {
            $method = self::methods($this->backend, 'delete', [
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
    
    /**
     * Save block instance to appercode backend
     * @return Appercode\Contracts\Onboarding\Block
     * @throws Appercode\Exceptions\Onboarding\Block\SaveException
     */
    public function save(): BlockContract
    {
        try {
            $method = self::methods($this->backend, 'update', [
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
}
