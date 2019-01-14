<?php

namespace Appercode\Onboarding;

use Appercode\Onboarding\Entity;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Roadmap as RoadmapContract;

use Illuminate\Support\Collection;

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

    /**
     * Current roadmap blocks (Appercode\Onboarding\Block) list
     * @var Illuminate\Support\Collection
     */
    private $blocks;

    /**
     * Current roadmap tasks (Appercode\Onboarding\Task) list
     * @var Illuminate\Support\Collection
     */
    private $tasks;

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

    /**
     * Current roadmap blocks list
     * @param  array  $filter
     * @return Illuminate\Support\Collection
     */
    public function blocks(array $filter): Collection
    {
        if (is_null($this->blocks)) {
            if (!count($this->blockIds)) {
                $this->blocks = new Collection([]);
            }

            $filter['where']['id']['$in'] = array_values(array_unique($this->blockIds));
            $this->blocks = Block::list($this->backend, $filter)->mapWithKeys(function (Block $block) {
                return [$block->id => $block];
            });
        }
        
        return $this->blocks;
    }

    /**
     * Current roadmap tasks list
     * @param  array  $filter
     * @return Illuminate\Support\Collection
     */
    public function tasks(array $filter): Collection
    {
        if (is_null($this->tasks)) {
            $blocks = $this->blocks(['take' => -1]);
            $tasksIds = [];
            foreach ($blocks as $block) {
                foreach ($block->tasks as $task) {
                    $tasksIds[] = $task['TaskId'];
                }
            }

            if (!count($tasksIds)) {
                return new Collection([]);
            }

            $filter['where']['id']['$in'] = array_values(array_unique($tasksIds));
            
            $this->tasks = Task::list($this->backend, $filter)->mapWithKeys(function (Task $task) {
                return [$task->id => $task];
            });
        }

        return $this->tasks;
    }
}
