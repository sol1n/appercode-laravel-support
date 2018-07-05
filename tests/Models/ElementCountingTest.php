<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

use Illuminate\Support\Facades\Cache;

class ElementCountingTest extends TestCase
{
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
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        
        $testSchema = $this->createSchema($user->backend);
        
        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::Create($testSchema->id, [
                'title' => str_random(20)
            ], $user->backend);
        }

        $count = Element::count($testSchema->id, $user->backend);

        $testSchema->delete();
        
        $this->assertEquals($elementsToCreation, $count);
    }

    public function test_elements_can_counting_with_filter()
    {
        $elementsToCreation = 2;
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        
        $testSchema = $this->createSchema($user->backend);
        
        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::Create($testSchema->id, [
                'title' => (string) $i
            ], $user->backend);
        }

        $count = Element::count($testSchema->id, $user->backend, [
            'where' => [
                'title' => '0'
            ]
        ]);

        $testSchema->delete();
        
        $this->assertEquals(1, $count);
    }

    public function test_elements_can_counting_via_elements_manager_with_caching()
    {
        $elementsToCreation = 2;
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        
        $testSchema = $this->createSchema($user->backend);
        
        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::Create($testSchema->id, [
                'title' => (string) $i
            ], $user->backend);
        }

        $elementManager = new ElementManager($user->backend);

        $count = $elementManager->count($testSchema->id, [
            'where' => [
                'title' => '0'
            ]
        ]);

        $this->assertEquals(1, $count);

        for ($i = 0; $i < $elementsToCreation; $i++) {
            Element::Create($testSchema->id, [
                'title' => (string) $i
            ], $user->backend);
        }

        $count = $elementManager->count($testSchema->id, [
            'where' => [
                'title' => '0'
            ]
        ]);

        $this->assertEquals(2, $count);

        $testSchema->delete();
    }
}
