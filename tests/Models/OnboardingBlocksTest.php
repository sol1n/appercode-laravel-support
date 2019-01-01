<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Onboarding\Block;

class OnboardingBlocksTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
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
        $block = Block::create($this->blockData(), $this->user->backend);

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
        $block = Block::create($this->blockData(), $this->user->backend);

        $block->delete();

        $block = Block::find($block->id, $this->user->backend);
        $this->assertEquals($block->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_block_can_be_deleted_via_static_method()
    {
        $block = Block::create($this->blockData(), $this->user->backend);

        Block::remove($block->id, $this->user->backend);

        $block = Block::find($block->id, $this->user->backend);
        $this->assertEquals($block->isDeleted, true);
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function disabledtest_blocks_can_be_counted()
    {
        $block = Block::create(array_merge($this->blockData(), [
            'title' => 'title for filtering'
        ]), $this->user->backend);

        $blocksCount = Block::count($this->user->backend, [
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
        $block = Block::create($this->blockData(), $this->user->backend);

        Block::update($block->id, ['title' => 'new title'], $this->user->backend);

        $block = Block::find($block->id, $this->user->backend);
        $this->assertEquals($block->title, 'new title');

        $block->delete();
    }

    /**
     * @group onboarding
     * @group blocks
     */
    public function test_blocks_can_be_saved()
    {
        $block = Block::create($this->blockData(), $this->user->backend);

        $block->title = 'new title';
        $block->save();

        $block = Block::find($block->id, $this->user->backend);
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

            Block::create($data, $this->user->backend);
        }

        $blocks = Block::list($this->user->backend, [
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
