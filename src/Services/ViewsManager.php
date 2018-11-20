<?php

namespace Appercode\Services;

use Appercode\User;
use Appercode\Backend;

use Appercode\Traits\AppercodeRequest;
use Appercode\Traits\SchemaName;

use Carbon\Carbon;
use Illuminate\Support\Collection;

use Appercode\Contracts\Element;
use Appercode\Contracts\Services\ViewsManager as ViewsManagerInterface;

class ViewsManager implements ViewsManagerInterface
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

        self::Request([
            'method' => $method['type'],
            'json' => $changes,
            'headers' => [
                'X-Appercode-Session-Token' => $this->backend->token()
            ],
            'url' => $method['url'],
        ]);
    }

    public function sendView(User $user, Element $element): void
    {
        $method = self::methods($this->backend, 'sendUserEvents');
        $beginView = [
            'schemaId' => $element->schemaName,
            'objectId' => $element->id,
            'viewType' => 'begin',
            'dateTime' => Carbon::now()->subMinutes(1)->toAtomString(),
            'clientKey' => $this->clientKey
        ];

        $endView = [
            'schemaId' => $element->schemaName,
            'objectId' => $element->id,
            'viewType' => 'end',
            'dateTime' => Carbon::now()->toAtomString(),
            'clientKey' => $this->clientKey
        ];

        self::Request([
            'method' => $method['type'],
            'json' => [
                $beginView, $endView
            ],
            'headers' => [
                'X-Appercode-Session-Token' => $this->backend->token()
            ],
            'url' => $method['url'],
        ]);
    }

    public function getBadges($schema): Collection
    {
        $schemaName = self::getSchemaName($schema);
        $method = self::methods($this->backend, 'getBadges', ['schema' => $schemaName]);

        $badges = self::jsonRequest([
            'method' => $method['type'],
            'headers' => [
                'X-Appercode-Session-Token' => $this->backend->token()
            ],
            'url' => $method['url'],
        ]);

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

        $views = self::jsonRequest([
            'method' => $method['type'],
            'json' => $elementIds,
            'headers' => [
                'X-Appercode-Session-Token' => $this->backend->token()
            ],
            'url' => $method['url'],
        ]);

        return (new Collection($views))->mapWithKeys(function($view) {
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
