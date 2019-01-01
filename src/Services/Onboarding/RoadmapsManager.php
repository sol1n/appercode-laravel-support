<?php

namespace Appercode\Services\Onboarding;

use Appercode\Onboarding\Roadmap;
use Appercode\Contracts\Backend;

class RoadmapsManager
{
    private $backend;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    public function create(array $fields): Roadmap
    {
        return Roadmap::create($fields, $this->backend);
    }

    public function find(string $id): Roadmap
    {
        return Roadmap::find($id, $this->backend);
    }

    public function count(array $filter = [])
    {
        return Roadmap::count($this->backend, $filter);
    }

    public function list(array $filter = [])
    {
        return Roadmap::list($this->backend, $filter);
    }

    public function update(string $id, array $fields)
    {
        return Roadmap::update($id, $fields, $this->backend);
    }

    public function delete(string $id)
    {
        return Roadmap::remove($id, $this->backend);
    }
}
