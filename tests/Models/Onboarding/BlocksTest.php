<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Services\OnboardingManager;

class BlocksTest extends TestCase
{
    private $user;
    private $manager;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        $this->manager = new OnboardingManager($this->user->backend);
    }

    protected function blockData()
    {
        return [
            'title' => 'block title',
            'icons' => [
                'unavailable' => 'https://via.placeholder.com/150x150.svg',
                'available' => 'https://via.placeholder.com/150x150.svg'
            ],
            'taskIds' => [
                '00000000-0000-0000-0000-000000000000'
            ],
            'orderIndex' => 10
        ];
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_block_can_be_created()
    {
        $block = $this->manager->blocks()->create($this->blockData());

        foreach ($this->blockData() as $index => $value) {
            $this->assertEquals($block->{$index}, $value);
        }

        $this->assertEquals(empty($block->id), false);
        $this->assertEquals(isset($block->isDeleted), true);
        $this->assertEquals(empty($block->updatedBy), false);

        $block->delete();
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_block_can_be_deleted()
    {
        $block = $this->manager->blocks()->create($this->blockData());

        $block->delete();

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_block_can_be_deleted_via_static_method()
    {
        $block = $this->manager->blocks()->create($this->blockData());

        $this->manager->blocks()->delete($block->id);

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function disabled_test_blocks_can_be_counted()
    {
        $block = $this->manager->blocks()->create(array_merge($this->blockData(), [
            'title' => 'title for filtering'
        ]));

        $blocksCount = $this->manager->blocks()->count($this->user->backend, [
            'title' => 'title for filtering'
        ]);

        $this->assertEquals($blocksCount, 1);

        $block->delete();
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_blocks_can_be_updated_via_static_method()
    {
        $block = $this->manager->blocks()->create($this->blockData());

        $this->manager->blocks()->update($block->id, ['title' => 'new title']);

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->title, 'new title');

        $block->delete();
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_blocks_can_be_saved()
    {
        $block = $this->manager->blocks()->create($this->blockData());

        $block->title = 'new title';
        $block->save();

        $block = $this->manager->blocks()->find($block->id);
        $this->assertEquals($block->title, 'new title');

        $block->delete();
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function disabled_test_blocks_can_be_listed()
    {
        for ($i = 0; $i < 3; $i++) {
            $data = $this->blockData();
            $data['title'] = $i;

            $this->manager->blocks()->create($data);
        }

        $blocks = $this->manager->blocks()->list([
            'where' => [
                'title' => [
                    '$in' => ['0', '1', '2']
                ]
            ]
        ]);

        $this->assertEquals($blocks->count(), 3);
        $blocks->each(function (Block $block) {
            $block->delete();
        });
    }
}
