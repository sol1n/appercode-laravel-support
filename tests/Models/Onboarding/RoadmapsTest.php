<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Services\OnboardingManager;

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

    /**
     * @group onboarding
     * @group roadmaps
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
     * @group roadmaps
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
     * @group roadmaps
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
     * @group roadmaps
     */
    public function disabled_test_roadmaps_can_be_counted()
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
     * @group roadmaps
     */
    public function test_blocks_can_be_updated_via_static_method()
    {
        $roadmap = $this->manager->roadmaps()->create($this->roadmapData());

        $this->manager->roadmaps()->update($roadmap->id, ['title' => 'new title']);

        $roadmap = $this->manager->roadmaps()->find($roadmap->id);
        $this->assertEquals($roadmap->title, 'new title');

        $roadmap->delete();
    }

    /**
     * @group onboarding
     * @group roadmaps
     */
    public function test_blocks_can_be_saved()
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
     * @group roadmaps
     */
    public function disabled_test_blocks_can_be_listed()
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
}
