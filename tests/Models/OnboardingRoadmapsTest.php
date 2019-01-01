<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Onboarding\Roadmap;

class OnboardingRoadmapsTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
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
        $roadmap = Roadmap::create($this->roadmapData(), $this->user->backend);

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
        $roadmap = Roadmap::create($this->roadmapData(), $this->user->backend);

        $roadmap->delete();

        $roadmap = Roadmap::find($roadmap->id, $this->user->backend);
        $this->assertEquals($roadmap->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group roadmaps
     */
    public function test_roadmap_can_be_deleted_via_static_method()
    {
        $roadmap = Roadmap::create($this->roadmapData(), $this->user->backend);

        Roadmap::remove($roadmap->id, $this->user->backend);

        $roadmap = Roadmap::find($roadmap->id, $this->user->backend);
        $this->assertEquals($roadmap->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group roadmaps
     */
    public function disabled_test_roadmaps_can_be_counted()
    {
        $roadmap = Roadmap::create(array_merge($this->roadmapData(), [
            'title' => 'title for filtering'
        ]), $this->user->backend);

        $roadmapsCount = Roadmap::count($this->user->backend, [
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
        $roadmap = Roadmap::create($this->roadmapData(), $this->user->backend);

        Roadmap::update($roadmap->id, ['title' => 'new title'], $this->user->backend);

        $roadmap = Roadmap::find($roadmap->id, $this->user->backend);
        $this->assertEquals($roadmap->title, 'new title');

        $roadmap->delete();
    }

    /**
     * @group onboarding
     * @group roadmaps
     */
    public function test_blocks_can_be_saved()
    {
        $roadmap = Roadmap::create($this->roadmapData(), $this->user->backend);

        $roadmap->title = 'new title';
        $roadmap->save();

        $roadmap = Roadmap::find($roadmap->id, $this->user->backend);
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

            Roadmap::create($data, $this->user->backend);
        }

        $roadmaps = Roadmap::list($this->user->backend, [
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
