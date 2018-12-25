<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Onboarding\Task;

class OnboardingTasksTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
    }

    protected function taskData()
    {
        return [
            'title' => 'task title',
            'subtitle' => 'task subtitle',
            'imageFileId' => '00000000-0000-0000-0000-000000000000',
            'description' => 'task description',
            'isRequired' => true,
            'beginAt' => 1,
            'endAt' => 9,
            'orderIndex' => 0,
            'confirmationFormId' => '00000000-0000-0000-0000-000000000000',
            'confirmationType' => TASK::CONFIRMATION_TYPE_BY_MENTOR,
            'reward' => [
                'points' => 12
            ]
        ];
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_created()
    {
        $task = Task::create($this->taskData(), $this->user->backend);

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
        $task = Task::create($this->taskData(), $this->user->backend);

        $task->delete();

        $task = Task::find($task->id, $this->user->backend);

        $this->assertEquals($task->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_counted()
    {
        $data = $this->taskData();
        $data['confirmationType'] = Task::CONFIRMATION_TYPE_BY_ADMINISTRATOR;
        $task = Task::create($data, $this->user->backend);

        $tasksCount = Task::count($this->user->backend, [
            'confirmationType' => Task::CONFIRMATION_TYPE_BY_ADMINISTRATOR
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

            $tasks[] = Task::create($data, $this->user->backend);
        }

        $tasks = Task::list($this->user->backend, [
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
        $task = Task::create($this->taskData(), $this->user->backend);
        $task->title = 'new title';
        $task->save();
        $task = Task::find($task->id, $this->user->backend);

        $this->assertEquals($task->title, 'new title');
        $task->delete();
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_updated_via_static_method()
    {
        $task = Task::create($this->taskData(), $this->user->backend);
        Task::update($task->id, [
            'title' => 'new title'
        ], $this->user->backend);
        $task = Task::find($task->id, $this->user->backend);

        $this->assertEquals($task->title, 'new title');
        $task->delete();
    }

    /**
     * @group onboarding
     * @group tasks
     */
    public function test_task_can_be_deleted_via_static_method()
    {
        $task = Task::create($this->taskData(), $this->user->backend);

        Task::remove($task->id, $this->user->backend);

        $task = Task::find($task->id, $this->user->backend);

        $this->assertEquals($task->isDeleted, true);
    }
}
