<?php

namespace Appercode\Onboarding;

use GuzzleHttp\Exception\BadResponseException;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Task as TaskContract;

use Appercode\Exceptions\Onboarding\Task\CreateException;
use Appercode\Exceptions\Onboarding\Task\DeleteException;
use Appercode\Exceptions\Onboarding\Task\RecieveException;
use Appercode\Exceptions\Onboarding\Task\SaveException;

use Appercode\Traits\AppercodeRequest;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class Task implements TaskContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $createdAt;
    public $updatedAt;
    public $updatedBy;
    public $isDeleted;
    public $reward;
    public $confirmationType;
    public $confirmationFormId;
    public $orderIndex;
    public $beginAt;
    public $endAt;
    public $isRequired;
    public $description;
    public $imageFileId;
    public $subtitle;
    public $title;

    const CONFIRMATION_TYPE_BY_PERFORMER = 'byPerformer';
    const CONFIRMATION_TYPE_BY_MENTOR = 'byMentor';
    const CONFIRMATION_TYPE_BY_ADMINISTRATOR = 'byAdministrator';

    protected static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboardingTasks'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/onboardingTasks/' . $data['id']
                ];
            case 'get':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/onboardingTasks/' . $data['id']
                ];
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboardingTasks/query?count=true'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboardingTasks/query'
                ];
            case 'update':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/onboardingTasks/' . $data['id']
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

        $this->reward = $data['reward'] ?? [];
        $this->confirmationType = $data['confirmationType'];
        $this->confirmationFormId = $data['confirmationFormId'] ?? null;
        $this->orderIndex = $data['orderIndex'] ?? null;
        $this->beginAt = (int) $data['beginAt'] ?? null;
        $this->endAt = (int) $data['endAt'] ?? null;

        $this->isRequired = (bool) $data['isRequired'];
        $this->description = $data['description'] ?? null;
        $this->imageFileId = $data['imageFileId'] ?? null;
        $this->subtitle = $data['subtitle'] ?? null;
        $this->title = $data['title'] ?? null;

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
            'subtitle' => $this->subtitle,
            'imageFileId' => $this->imageFileId,
            'description' => $this->description,
            'isRequired' => $this->isRequired,
            'beginAt' => $this->beginAt,
            'endAt' => $this->endAt,
            'orderIndex' => $this->orderIndex,
            'confirmationFormId' => $this->confirmationFormId,
            'confirmationType' => $this->confirmationType,
            'reward' => (object) $this->reward
        ];
    }

    /**
     * Creates new task and returns it instance
     * @param  array   $fields
     * @param  Appercode\Contracts\Backend $backend
     * @return Appercode\Contracts\Onboarding\Task
     * @throws Appercode\Exceptions\Onboarding\Task\CreateException
     */
    public static function create(array $fields, Backend $backend): TaskContract
    {
        try {
            $method = self::methods($backend, 'create');

            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new Task($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, $data);
        }
    }

    /**
     * Returns task by id
     * @param  string   id
     * @param  Appercode\Contracts\Backend $backend
     * @return Appercode\Contracts\Onboarding\Task
     * @throws Appercode\Exceptions\Onboarding\Task\RecieveException
     */
    public static function find(string $id, Backend $backend): TaskContract
    {
        try {
            $method = self::methods($backend, 'get', [
                'id' => $id
            ]);

            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => ['X-Appercode-Session-Token' => $backend->token()],
                'url' => $method['url'],
            ]);

            return new Task($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new RecieveException($message, $code, $e, [
                'id' => $this->id
            ]);
        }
    }

    /**
     * Returns tasks count
     * @param  Appercode\Contracts\Backend $backend
     * @param  array   $filter
     * @return int
     * @throws Appercode\Exceptions\Onboarding\Task\RecieveException
     */
    public static function count(Backend $backend, array $filter = []): int
    {
        try {
            $method = self::methods($backend, 'count');

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
     * Lists tasks with order, filter
     * @param  Appercode\Contracts\Backend $backend
     * @param  array   $filter
     * @return Illuminate\Support\Collection
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
                return new Task($data, $backend);
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
     * Save fields for provided task id
     * @param  string  $id
     * @param  array   $fields
     * @param  Appercode\Contracts\Backend $backend
     * @return void
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
     * Updates task instance fields to appercode backend
     * @return Appercode\Contracts\Onboarding\Task
     */
    public function save(): TaskContract
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

    /**
     * Removes task and returns instance
     * @return Appercode\Contracts\Onboarding\Task
     * @throws Appercode\Exceptions\Onboarding\Task\DeleteException
     */
    public function delete(): TaskContract
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
}
