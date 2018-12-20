<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\Backend;
use Appercode\Contracts\NotificationCompany as NotificationCompanyContract;

use Appercode\Exceptions\NotificationCompany\CreateException;
use Appercode\Exceptions\NotificationCompany\ReceiveException;
use Appercode\Exceptions\NotificationCompany\SaveException;
use Appercode\Exceptions\NotificationCompany\DeleteException;
use Appercode\Exceptions\NotificationCompany\SendException;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class NotificationCompany implements NotificationCompanyContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
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
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/query'
                ];
            case 'save':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/' . $data['id']
                ];
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/query?count=true'
                ];
            case 'find':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/' . $data['id']
                ];
            case 'send':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/notifications/campaigns/' . $data['id'] . '/send'
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

    protected function toJson()
    {
        return [
            'id' => $this->id,
            'sentAt' => is_null($this->sentAt)
               ? null
               : $this->isPublished->setTimezone('Europe/Moscow')->toAtomString(),
            'title' => $this->title,
            'body' => $this->body,
            'deepLink' => $this->deepLink,
            'to' => $this->to,
            'scheduledAt' => is_null($this->scheduledAt)
                ? null
                : $this->scheduledAt->setTimezone('Europe/Moscow')->toAtomString(),
            'withPushNotification' => $this->withPushNotification,
            'withBadgeNotification' => $this->withBadgeNotification,
            'installationFilter' => $this->installationFilter,
            'updatedAt' => is_null($this->updatedAt)
                ? null
                : $this->updatedAt->setTimezone('Europe/Moscow')->toAtomString(),
        ];
    }

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
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
        return collect(self::jsonRequest([
            'method' => $method['type'],
            'json' => $filter,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]))->map(function ($item) use ($backend) {
            return new self($item, $backend);
        });
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
        $method = self::methods($backend, 'save', ['id' => $id]);

        try {
            self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new SaveException($message, $code, $e, ['fields' => $fields, 'id' => $id]);
        }
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

    public static function count(Backend $backend, array $fields = []): int
    {
        $method = self::methods($backend, 'count');

        return self::countRequest([
            'method' => $method['type'],
            'json' => (object) $fields,
            'headers' => [
                'X-Appercode-Session-Token' => $backend->token()
            ],
            'url' => $method['url'],
        ]);
    }

    public static function sendStatic(Backend $backend, array $ids): void
    {
        foreach ($ids as $id) {
            $method = self::methods($backend, 'send', ['id' => $id]);

            try {
                self::request([
                    'method' => $method['type'],
                    'headers' => [
                        'X-Appercode-Session-Token' => $backend->token()
                    ],
                    'url' => $method['url'],
                ]);
            } catch (BadResponseException $e) {
                $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
                $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

                throw new SendException($message, $code, $e, ['id' => $id]);
            }
        }
    }

    public static function deleteStatic(Backend $backend, array $ids): void
    {
        foreach ($ids as $id) {
            $method = self::methods($backend, 'delete', ['id' => $id]);

            try {
                self::request([
                    'method' => $method['type'],
                    'headers' => [
                        'X-Appercode-Session-Token' => $backend->token()
                    ],
                    'url' => $method['url'],
                ]);
            } catch (BadResponseException $e) {
                $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
                $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

                throw new DeleteException($message, $code, $e, ['id' => $id]);
            }
        }
    }

    public function save(): NotificationCompanyContract
    {
        $method = self::methods($this->backend, 'save', ['id' => $this->id]);

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $this->toJson(),
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);

            return new self($json, $this->backend);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new SaveException($message, $code, $e, ['fields' => $this->toJson()]);
        }
    }

    public function send(): NotificationCompanyContract
    {
        $method = self::methods($this->backend, 'send', ['id' => $this->id]);

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

            throw new SendException($message, $code, $e, ['id' => $this->id]);
        }

        return $this;
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
