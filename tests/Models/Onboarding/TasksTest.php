<?php

namespace Tests\Models\Onboarding;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Onboarding\Task;
use Appercode\Services\OnboardingManager;

use Tests\Models\Onboarding\ExampleData;

class TasksTest extends TestCase
{
    use ExampleData;
    
    private $user;
    private $manager;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        $this->manager = new OnboardingManager($this->user->backend);
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_created()
    {
        $task = $this->manager->tasks()->create($this->taskData());

        foreach ($this->taskData() as $index => $value) {
            $this->assertEquals($task->{$index}, $value);
        }

        $this->assertEquals(empty($task->id), false);
        $this->assertEquals(isset($task->isDeleted), true);
        $this->assertEquals(empty($task->updatedBy), false);

        $task->delete();
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_deleted()
    {
        $task = $this->manager->tasks()->create($this->taskData());

        $task->delete();

        $task = $this->manager->tasks()->find($task->id);

        $this->assertEquals($task->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_counted()
    {
        $data = $this->taskData();
        $data['title'] = '25';
        $task = $this->manager->tasks()->create($data);

        $tasksCount = $this->manager->tasks()->count([
            'title' => '25'
        ]);

        $this->assertEquals($tasksCount, 1);

        $task->delete();
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_listed()
    {
        for ($i = 0; $i < 3; $i++) {
            $data = $this->taskData();
            $data['title'] = $i;

            $this->manager->tasks()->create($data);
        }

        $tasks = $this->manager->tasks()->list([
            'where' => [
                'title' => [
                    '$in' => ['0', '1', '2']
                ]
            ]
        ]);

        $this->assertEquals($tasks->count(), 3);
        $tasks->each(function (Task $task) {
            $task->delete();
        });
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_saved()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $task->title = 'new title';
        $task->save();
        $task = $this->manager->tasks()->find($task->id);

        $this->assertEquals($task->title, 'new title');
        $task->delete();
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_updated_via_static_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());
        $this->manager->tasks()->update($task->id, [
            'title' => 'new title'
        ]);
        $task = $this->manager->tasks()->find($task->id);

        $this->assertEquals($task->title, 'new title');
        $task->delete();
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_deleted_via_static_method()
    {
        $task = $this->manager->tasks()->create($this->taskData());

        $this->manager->tasks()->delete($task->id);

        $task = $this->manager->tasks()->find($task->id);

        $this->assertEquals($task->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_class_returns_confirmation_types()
    {
        $types = Task::confirmationTypes();
        $this->assertEquals($types->count(), 4);
    }
}
