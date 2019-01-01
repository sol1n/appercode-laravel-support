<?php

namespace Appercode\Onboarding;

use Appercode\Onboarding\Entity;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Roadmap as RoadmapContract;

class Roadmap extends Entity implements RoadmapContract
{
    /**
     * Roadmap title
     * @var string
     */
    public $title;

    /**
     * Child blocks (Appercode\Onboarding\Block) ids
     * @var array
     */
    public $blockIds;

    /**
     * User groups ids
     * @var array
     */
    public $groupIds;

    protected static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/roadmaps'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/onboarding/roadmaps/' . $data['id']
                ];
            case 'get':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/onboarding/roadmaps/' . $data['id']
                ];
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/roadmaps/query?count=true'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/roadmaps/query'
                ];
            case 'update':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/onboarding/roadmaps/' . $data['id']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        parent::__construct($data, $backend);

        $this->title = $data['title'] ?? null;
        $this->blockIds = $data['blockIds'] ?? [];
        $this->groupIds = $data['groupIds'] ?? [];

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
            'blockIds' => $this->blockIds,
            'groupIds' => $this->groupIds
        ];
    }
}
