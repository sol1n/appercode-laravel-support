<?php

namespace Appercode\Services;

use Appercode\User;
use Appercode\Backend;

use Appercode\Traits\AppercodeRequest;
use Appercode\Traits\SchemaName;

use Appercode\Exceptions\Element\BadgesGetException;
use Appercode\Exceptions\Element\BadgesPutException;
use Appercode\Exceptions\Element\ViewsSendException;
use Appercode\Exceptions\Element\ViewsGetException;

use Appercode\Contracts\Element;
use Appercode\Contracts\Services\ViewsManager as ViewsManagerContract;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class ViewsManager implements ViewsManagerContract
{
    use AppercodeRequest, SchemaName;

    protected $backend;

    public $clientKey;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
        $this->clientKey = md5($backend->user->id);
    }

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'getBadges':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/views/badges/' . $data['schema']
                ];
            case 'putBadges':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/views/badges/' . $data['schema']
                ];
            case 'sendUserEvents':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/views/userEvents'
                ];
            case 'getViews':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/views/' . $data['schema'] . '/query'
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function putBadges($schema, array $changes): void
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($this->backend, 'putBadges', ['schema' => $schemaName]);

        try {
            self::Request([
                'method' => $method['type'],
                'json' => $changes,
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new BadgesPutException($message, $code, $e, $changes);
        }
    }

    public function sendView(User $user, Element $element): void
    {
        $method = self::methods($this->backend, 'sendUserEvents');

        $events = [
            [
                'schemaId' => $element->schemaName,
                'objectId' => $element->id,
                'viewType' => 'begin',
                'dateTime' => Carbon::now()->subMinutes(1)->toAtomString(),
                'clientKey' => $this->clientKey
            ],
            [
                'schemaId' => $element->schemaName,
                'objectId' => $element->id,
                'viewType' => 'end',
                'dateTime' => Carbon::now()->toAtomString(),
                'clientKey' => $this->clientKey
            ]
        ];

        try {
            self::Request([
                'method' => $method['type'],
                'json' => $events,
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ViewsSendException($message, $code, $e, $events);
        }
    }

    public function getBadges($schema): Collection
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($this->backend, 'getBadges', ['schema' => $schemaName]);

        try {
            $badges = self::jsonRequest([
                'method' => $method['type'],
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new BadgesGetException($message, $code, $e);
        }

        return new Collection($badges);
    }

    /**
     * Return views count for elements in provided collection
     * @see http://test.appercode.com/v1/forms/swagger/#!/Views32Tracking/ViewsBySchemaIdQueryPost
     * @param  string|Appercode\Contracts\Schema $schema
     * @param  array  $elementIds
     * @return Illuminate\Support\Collection
     */
    public function views($schema, array $elementIds = []): Collection
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($this->backend, 'getViews', ['schema' => $schemaName]);

        try {
            $views = self::jsonRequest([
                'method' => $method['type'],
                'json' => $elementIds,
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ViewsGetException($message, $code, $e, $elementIds);
        }

        return (new Collection($views))->mapWithKeys(function ($view) {
            return [$view['objectId'] => $view];
        });
    }

    /**
     * Send badge notifications for map of elements-users
     * @param  string|Appercode\Contracts\Schema $schema
     * @param  array  $map  like [$elementId => [$userId, $userId]]
     * @return void
     */
    public function addBadges($schema, array $map): void
    {
        $this->putBadges($schema, [
            'add' => $map
        ]);
    }

    /**
     * Removes badge notifications for map of elements-users
     * @param  string|Appercode\Contracts\Schema $schema
     * @param  array  $map  like [$elementId => [$userId, $userId]]
     * @return void
     */
    public function removeBadges($schema, array $map): void
    {
        $this->putBadges($schema, [
            'remove' => $map
        ]);
    }
}
