<?php

namespace Tests\Unit\Onboarding;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Onboarding\Block;
use Appercode\Services\OnboardingManager;

use Appercode\Enums\Onboarding\Task\ConfirmationTypes;

class BlocksTest extends TestCase
{
    private $user;
    private $manager;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        $this->manager = new OnboardingManager($this->user->backend);
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
     * @group onboarding.blocks
     */
    public function test_block_can_be_created()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $blockData = $this->blockData($task->id);
        $block = $this->manager->blocks()->create($blockData);

        $this->assertEquals(empty($block->id), false);
        $this->assertEquals(isset($block->isDeleted), true);
        $this->assertEquals(empty($block->updatedBy), false);

        $this->assertEquals($block->title, $blockData['title']);
        $this->assertEquals($block->icons, $blockData['icons']);
        $this->assertEquals($block->orderIndex, $blockData['orderIndex']);

        $blockTask = $block->tasks->first();
        $blockTaskData = $blockData['tasks'][0];

        $this->assertEquals($blockTask['taskId'], $task->id);
        $this->assertEquals($blockTask['isRequired'], $blockTaskData['isRequired']);
        $this->assertEquals($blockTask['beginAt'], $blockTaskData['beginAt']);
        $this->assertEquals($blockTask['endAt'], $blockTaskData['endAt']);
        $this->assertEquals($blockTask['orderIndex'], $blockTaskData['orderIndex']);

        $block->delete();
        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_block_can_be_deleted()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));

        $block->delete();

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->isDeleted, true);
        
        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_block_can_be_deleted_via_static_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));
        $this->manager->blocks()->delete($block->id);

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->isDeleted, true);

        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_blocks_can_be_counted()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create(array_merge($this->blockData($task->id), [
            'title' => 'title for filtering'
        ]));

        $blocksCount = $this->manager->blocks()->count([
            'title' => 'title for filtering'
        ]);

        $this->assertEquals($blocksCount, 1);

        $block->delete();
        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_blocks_can_be_updated_via_static_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));

        $this->manager->blocks()->update($block->id, ['title' => 'new title']);

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->title, 'new title');

        $block->delete();
        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_blocks_can_be_saved()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));

        $block->title = 'new title';
        $block->save();

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->title, 'new title');

        $block->delete();
        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_blocks_can_be_listed()
    {
        $task = $this->manager->tasks()->create($this->taskData());

        for ($i = 0; $i < 3; $i++) {
            $data = $this->blockData($task->id);
            $data['title'] = $i;

            $this->manager->blocks()->create($data);
        }

        $blocks = $this->manager->blocks()->list([
            'where' => [
                'title' => [
                    '$in' => ['0', '1', '2']
                ]
            ]
        ]);

        $this->assertEquals($blocks->count(), 3);
        $blocks->each(function (Block $block) {
            $block->delete();
        });

        $task->delete();
    }

    /**
     * @group onboarding
     * @group onboarding.blocks
     */
    public function test_block_tasks_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $block = $this->manager->blocks()->create($this->blockData($task->id));

        $tasks = $block->tasks(['take' => -1]);
        $this->assertEquals($tasks->count(), 1);

        $fetchedTask = $tasks->first();
        $this->assertEquals($fetchedTask->id, $task->id);

        $block->delete();
        $task->delete();
    }
}
