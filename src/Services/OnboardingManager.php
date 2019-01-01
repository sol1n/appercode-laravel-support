<?php

namespace Appercode\Services;

use Appercode\Contracts\Backend;
use Appercode\Services\Onboarding\TasksManager;
use Appercode\Services\Onboarding\BlocksManager;
use Appercode\Services\Onboarding\RoadmapsManager;

class OnboardingManager
{
    private $backend;
    private $tasksManager;
    private $blocksManager;
    private $roadmapsManager;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
        $this->tasksManager = new TasksManager($backend);
        $this->blocksManager = new BlocksManager($backend);
        $this->roadmapsManager = new RoadmapsManager($backend);
    }

    public function tasks()
    {
        return $this->tasksManager;
    }

    public function blocks()
    {
        return $this->blocksManager;
    }

    public function roadmaps()
    {
        return $this->roadmapsManager;
    }
}
