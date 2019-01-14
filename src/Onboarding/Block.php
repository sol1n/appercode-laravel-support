<?php

namespace Appercode\Onboarding;

use Appercode\Onboarding\Entity;
use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Block as BlockContract;
use Illuminate\Support\Collection;

class Block extends Entity implements BlockContract
{
    /**
     * Block title
     * @var string
     */
    public $title;

    /**
     * Statuses icons collection (available, unavailable)
     * @var array
     */
    public $icons;

    /**
     * Child tasks [
     *     'taskId' => string,
     *     'isRequired' => bool,
     *     'beginAt' => ?int
     *     'endAt' => ?int,
     *     'orderIndex' => int
     * ]
     * @var Illuminate\Support\Collection
     */
    public $task;

    /**
     * Order index in a block
     * @var int
     */
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
        parent::__construct($data, $backend);

        $this->title = $data['title'] ?? null;
        $this->icons = $data['icons'] ?? [];
        $this->orderIndex = $data['orderIndex'] ?? null;

        $this->tasks = isset($data['tasks']) && is_array($data['tasks'])
            ? (new Collection($data['tasks']))->sortBy('orderIndex')
            : [];

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
            'tasks' => $this->tasks->toArray(),
            'orderIndex' => $this->orderIndex
        ];
    }
}
