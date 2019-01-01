<?php

namespace Appercode\Services\Onboarding;

use Appercode\Onboarding\Task;
use Appercode\Contracts\Backend;

class TasksManager
{
    private $backend;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    public function create(array $fields): Task
    {
        return Task::create($fields, $this->backend);
    }

    public function find(string $id): Task
    {
        return Task::find($id, $this->backend);
    }

    public function count(array $filter = [])
    {
        return Task::count($this->backend, $filter);
    }

    public function list(array $filter = [])
    {
        return Task::list($this->backend, $filter);
    }

    public function update(string $id, array $fields)
    {
        return Task::update($id, $fields, $this->backend);
    }

    public function delete(string $id)
    {
        return Task::remove($id, $this->backend);
    }
}
