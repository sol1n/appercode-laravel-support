<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\NotificationCompany as NotificationCompanyContract;

use Appercode\Exceptions\NotificationCompany\CreateException;
use Appercode\Exceptions\NotificationCompany\ReceiveException;
use Appercode\Exceptions\NotificationCompany\DeleteException;

use Appercode\Backend;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class NotificationCompany implements NotificationCompanyContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $isPublished;
    public $isDeleted;
    public $updatedBy;
    public $updatedAt;
    public $createdAt;
    public $installationFilter;
    public $withBadgeNotification;
    public $withPushNotification;
    public $scheduledAt;
    public $to;
    public $deepLink;
    public $body;
    public $title;
    public $sentAt;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns'
                ];
            case 'list':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns'
                ];
            case 'find':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/' . $data['id']
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/' . $data['id']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
        $this->isPublished = (bool) $data['isPublished'];
        $this->sentAt = is_null($data['sentAt'])
            ? null
            : Carbon::parse($data['sentAt']);
        $this->title = $data['title'] ?? null;
        $this->body = $data['body'] ?? null;
        $this->deepLink = $data['deepLink'] ?? null;
        $this->to = $data['to'] ?? null;
        $this->scheduledAt = is_null($data['scheduledAt'])
            ? null
            : Carbon::parse($data['scheduledAt']);
        $this->withPushNotification = $data['withPushNotification'] ?? null;
        $this->withBadgeNotification = $data['withBadgeNotification'] ?? null;
        $this->installationFilter = $data['installationFilter'] ?? null;
        $this->createdAt = is_null($data['createdAt'])
            ? null
            : Carbon::parse($data['createdAt']);
        $this->updatedAt = is_null($data['updatedAt'])
            ? null
            : Carbon::parse($data['updatedAt']);
        $this->isDeleted = $data['isDeleted'] ?? null;
        $this->updatedBy = $data['updatedBy'] ?? null;

        $this->backend = $backend;
    }

    public static function list(Backend $backend, $filter = []): Collection
    {
        $method = self::methods($backend, 'list');
        $json = self::jsonRequest([
            'method' => $method['type'],
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);
    }

    public static function create(Backend $backend, array $fields): NotificationCompanyContract
    {
        $method = self::methods($backend, 'create');

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => (object) $fields,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, ['fields' => $fields]);
        }

        return new self($json, $backend);
    }

    public static function update(Backend $backend, array $fields, string $id): void
    {
    }

    public static function find(Backend $backend, string $id): NotificationCompanyContract
    {
        $method = self::methods($backend, 'find', ['id' => $id]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);

            return new self($json, $backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['fields' => $fields]);
        }

        return new self($json, $backend);
    }

    public static function sendStatic(Backend $backend, array $ids): void
    {
    }
    public static function deleteStatic(Backend $backend, array $ids): void
    {
    }

    public function save(): NotificationCompanyContract
    {
    }

    public function send(): NotificationCompanyContract
    {
    }

    public function delete(): NotificationCompanyContract
    {
        $method = self::methods($this->backend, 'delete', ['id' => $this->id]);

        try {
            self::request([
                'method' => $method['type'],
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new DeleteException($message, $code, $e, ['id' => $this->id]);
        }

        return $this;
    }
}
