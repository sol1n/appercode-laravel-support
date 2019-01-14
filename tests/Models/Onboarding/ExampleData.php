<?php

namespace Tests\Models\Onboarding;

use Appercode\Enums\Onboarding\Task\ConfirmationTypes;

trait ExampleData
{
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

    protected function blockTasksData(string $taskId)
    {
        return [
            [
                'id' => $taskId,
                'isRequired' => true,
                'beginAt' => 0,
                'endAt' => null,
                'orderIndex' => 10
            ]
        ];
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
}
