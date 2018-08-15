<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

class ElementCountingTest extends TestCase
{
    private $user;
    private $schema;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        $this->schema = $this->createSchema($this->user->backend);
    }

    protected function tearDown()
    {
        $this->schema->delete();
    }

    private function createSchema(Backend $backend)
    {
        return Schema::create([
            'id' => 'countingTestSchema',
            'title' => 'deleteMePlease',
            'fields' => [
                [
                    'name' => 'title',
                    'type' => SchemaFieldTypes::STRING
                ]
            ]
        ], $backend);
    }

    public function test_elements_can_counting()
    {
        $elementsToCreation = 2;
        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);
        }
        $this->assertEquals($elementsToCreation, Element::count($this->schema->id, $this->user->backend));
    }

    public function test_elements_can_counting_with_filter()
    {
        $elementsToCreation = 2;
        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);
        }
        
        $this->assertEquals(1, Element::count($this->schema->id, $this->user->backend, [
            'where' => [
                'title' => '0'
            ]
        ]));
    }

    public function test_elements_can_counting_via_elements_manager_with_caching()
    {
        $elementsToCreation = 2;
        app('config')->set('appercode.elements.caching.enabled', true);
        $elementManager = new ElementManager($this->user->backend);

        for ($i = 0; $i < $elementsToCreation; $i++) {
            $elementManager->create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);
        }

        $this->assertEquals(1, $elementManager->count($this->schema->id, [
            'where' => [
                'title' => '0'
            ]
        ]));

        for ($i = 0; $i < $elementsToCreation; $i++) {
            $elementManager->create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);
        }

        $this->assertEquals(2, $elementManager->count($this->schema->id, [
            'where' => [
                'title' => '0'
            ]
        ]));
    }

    public function test_elements_can_counting_via_elements_manager_without_caching()
    {
        $elementsToCreation = 2;
        app('config')->set('appercode.elements.caching.enabled', false);
        $elementManager = new ElementManager($this->user->backend);

        for ($i = 0; $i < $elementsToCreation; $i++) {
            $elementManager->create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);
        }

        $this->assertEquals(1, $elementManager->count($this->schema->id, [
            'where' => [
                'title' => '0'
            ]
        ]));

        for ($i = 0; $i < $elementsToCreation; $i++) {
            $elementManager->create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);
        }

        $this->assertEquals(2, $elementManager->count($this->schema->id, [
            'where' => [
                'title' => '0'
            ]
        ]));
    }

    /**
     * @group bulk
     */
    public function test_elements_can_counting_with_bulk_query()
    {
        $queries = [];
        $elementsToCreation = 20;

        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);

            $queries[] = [
                'count' => true,
                'where' => [
                    'title' => (string) $i
                ]
            ];
        }

        $results = Element::bulk($this->schema->id, $queries, $this->user->backend);

        foreach ($results as $one) {
            $this->assertArrayHasKey('count', $one);
            $this->assertEquals($one['count'], 1);
        }
    }

    /**
     * @group bulk
     */
    public function test_elements_can_counting_with_bulk_query_via_manager()
    {
        $elementsToCreation = 2;
        $elementManager = new ElementManager($this->user->backend);

        for ($i = 0; $i < $elementsToCreation; $i++) {
            $elementManager->create($this->schema->id, [
                'title' => (string) $i
            ], $this->user->backend);

            $queries[] = [
                'count' => true,
                'where' => [
                    'title' => (string) $i
                ]
            ];
        }

        $results = $elementManager->bulk($this->schema->id, $queries);

        foreach ($results as $one) {
            $this->assertArrayHasKey('count', $one);
            $this->assertEquals($one['count'], 1);
        }
    }
}
