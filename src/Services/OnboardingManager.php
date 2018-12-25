<?php

namespace Appercode\Services;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Task;

class OnboardingManager
{
    private $backend;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    public function createTask(array $fields): Task
    {
        return Task::create($fields, $this->backend);
    }

    public function findTask(string $id): Task
    {
        return Task::find($id, $this->backend);
    }

    public function countTasks(array $filter = [])
    {
        return Task::count($this->backend, $filter);
    }

    public function listTasks(array $filter = [])
    {
        return Task::list($this->backend, $filter);
    }

    public function updateTask(string $id, array $fields)
    {
        return Task::update($id, $fields, $this->backend);
    }

    public function deleteTask(string $id)
    {
        return Task::remove($id, $this->backend);
    }
}
