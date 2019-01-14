<?php

namespace Tests\Unit\Onboarding;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Onboarding\Task;
use Appercode\Onboarding\Block;
use Appercode\Onboarding\Roadmap;
use Appercode\Services\OnboardingManager;
use Appercode\Enums\Onboarding\Task\ConfirmationTypes;

class RoadmapsTest extends TestCase
{
    private $user;
    private $manager;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        $this->manager = new OnboardingManager($this->user->backend);
    }

    protected function roadmapData()
    {
        return [
            'title' => 'roadmap title',
            'blockIds' => [
                '00000000-0000-0000-0000-000000000000'
            ],
            'groupIds' => [
                '00000000-0000-0000-0000-000000000000'
            ]
        ];
    }

    protected function blockData(string $taskId)
    {
        return [
            'title' => 'block title',
            'icons' => [
                'unavailable' => 'https://via.placeholder.com/150x150.svg',
                'available' => 'https://via.placeholder.com/150x150.svg'
            ],
            'tasks' => $this->blockTasksData($taskId),
            'orderIndex' => 10
        ];
    }

    protected function taskData()
    {
        return [
            'title' => 'task title',
            'subtitle' => 'task subtitle',
            'description' => 'task description',
            'confirmationType' => ConfirmationTypes::CONFIRMATION_TYPE_BY_ADMINISTRATOR,
            'reward' => [
                'points' => 12
            ]
        ];
    }

    protected function blockTasksData(string $taskId)
    {
        return [
            [
                'taskId' => $taskId,
                'isRequired' => true,
                'beginAt' => 0,
                'endAt' => null,
                'orderIndex' => 10
            ]
        ];
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmap_can_be_created()
    {
        $roadmap = $this->manager->roadmaps()->create($this->roadmapData());

        foreach ($this->roadmapData() as $index => $value) {
            $this->assertEquals($roadmap->{$index}, $value);
        }

        $this->assertEquals(empty($roadmap->id), false);
        $this->assertEquals(isset($roadmap->isDeleted), true);
        $this->assertEquals(empty($roadmap->updatedBy), false);

        $roadmap->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmap_can_be_deleted()
    {
        $roadmap = $this->manager->roadmaps()->create($this->roadmapData());

        $roadmap->delete();

        $roadmap = $this->manager->roadmaps()->find($roadmap->id);
        $this->assertEquals($roadmap->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmap_can_be_deleted_via_static_method()
    {
        $roadmap = $this->manager->roadmaps()->create($this->roadmapData());

        $this->manager->roadmaps()->delete($roadmap->id);

        $roadmap = $this->manager->roadmaps()->find($roadmap->id);
        $this->assertEquals($roadmap->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmaps_can_be_counted()
    {
        $roadmap = $this->manager->roadmaps()->create(array_merge($this->roadmapData(), [
            'title' => 'title for filtering'
        ]));

        $roadmapsCount = $this->manager->roadmaps()->count([
            'title' => 'title for filtering'
        ]);

        $this->assertEquals($roadmapsCount, 1);

        $roadmap->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmaps_can_be_updated_via_static_method()
    {
        $roadmap = $this->manager->roadmaps()->create($this->roadmapData());

        $this->manager->roadmaps()->update($roadmap->id, ['title' => 'new title']);

        $roadmap = $this->manager->roadmaps()->find($roadmap->id);
        $this->assertEquals($roadmap->title, 'new title');

        $roadmap->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmaps_can_be_saved()
    {
        $roadmap = $this->manager->roadmaps()->create($this->roadmapData());

        $roadmap->title = 'new title';
        $roadmap->save();

        $roadmap = $this->manager->roadmaps()->find($roadmap->id);
        $this->assertEquals($roadmap->title, 'new title');

        $roadmap->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmaps_can_be_listed()
    {
        for ($i = 0; $i < 3; $i++) {
            $data = $this->roadmapData();
            $data['title'] = $i;

            $this->manager->roadmaps()->create($data);
        }

        $roadmaps = $this->manager->roadmaps()->list([
            'where' => [
                'title' => [
                    '$in' => ['0', '1', '2']
                ]
            ]
        ]);

        $this->assertEquals($roadmaps->count(), 3);
        $roadmaps->each(function (Roadmap $roadmap) {
            $roadmap->delete();
        });
    }

    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmap_blocks_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));

        $roadmapData = array_merge($this->roadmapData(), [
            'blockIds' => [$block->id]
        ]);

        $roadmap = $this->manager->roadmaps()->create($roadmapData);

        $blocks = $roadmap->blocks(['take' => -1]);

        $this->assertEquals($blocks->count(), 1);

        $fetchedBlock = $blocks->first();
        $this->assertEquals($fetchedBlock->id, $block->id);

        $roadmap->delete();
        $block->delete();
        $task->delete();
    }


    /**
     * @group onboarding
     * @group onboarding.roadmaps
     */
    public function test_roadmap_tasks_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));

        $roadmapData = array_merge($this->roadmapData(), [
            'blockIds' => [$block->id]
        ]);

        $roadmap = $this->manager->roadmaps()->create($roadmapData);

        $tasks = $roadmap->tasks(['take' => -1]);

        $this->assertEquals($tasks->count(), 1);

        $fetchedTask = $tasks->first();
        $this->assertEquals($fetchedTask->id, $task->id);

        $roadmap->delete();
        $block->delete();
        $task->delete();
    }
}
