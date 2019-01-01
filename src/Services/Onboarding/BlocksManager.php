<?php

namespace Appercode\Services\Onboarding;

use Appercode\Onboarding\Block;
use Appercode\Contracts\Backend;

class BlocksManager
{
    private $backend;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    public function create(array $fields): Block
    {
        return Block::create($fields, $this->backend);
    }

    public function find(string $id): Block
    {
        return Block::find($id, $this->backend);
    }

    public function count(array $filter = [])
    {
        return Block::count($this->backend, $filter);
    }

    public function list(array $filter = [])
    {
        return Block::list($this->backend, $filter);
    }

    public function update(string $id, array $fields)
    {
        return Block::update($id, $fields, $this->backend);
    }

    public function delete(string $id)
    {
        return Block::remove($id, $this->backend);
    }
}
