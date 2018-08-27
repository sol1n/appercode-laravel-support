<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

class ElementBulkOperations extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
    }

    private function createSchema(Backend $backend, $fields = [])
    {
        return Schema::create([
            'id' => 'elementsTestSchema',
            'title' => 'deleteMePlease',
            'fields' => $fields
        ], $backend);
    }

    /**
     * @group bulk
     */
    public function test_bulk_simple_update()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        $elements = [];

        for ($i = 0; $i < 5; $i++) {
            $element = Element::create($schema->id, ['stringSingleField' => 'title'], $this->user->backend);
            $elements[$element->id] = $element;
        }

        Element::bulkUpdate($schema, array_keys($elements), ['stringSingleField' => 'new-title'], $this->user->backend);

        $newElements = Element::list($schema, $this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => array_keys($elements)
                ]
            ]
        ]);

        foreach ($newElements as $element) {
            $this->assertEquals($element->fields['stringSingleField'], 'new-title');
        }

        $schema->delete();
    }

    /**
     * @group bulk
     */
    public function test_bulk_array_append_updates()
    {
        $additionalValues = ['4', '5', '6', '7'];
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringMultipleField',
                'type' => SchemaFieldTypes::STRING,
                'multiple' => true
            ]
        ]);

        for ($i = 0; $i < 5; $i++) {
            $field = [];

            for ($j = $i; $j < 5; $j++) {
                $field[] = (string) $j;
            }

            $element = Element::create($schema->id, ['stringMultipleField' => $field], $this->user->backend);
            $elements[$element->id] = $element;
        }

        Element::bulkUpdate($schema, array_keys($elements), ['stringMultipleField' => [
            'action' => 'append',
            'value' => $additionalValues
        ]], $this->user->backend);

        $newElements = Element::list($schema, $this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => array_keys($elements)
                ]
            ]
        ]);

        foreach ($newElements as $updatedElement) {
            $oldElement = $elements[$updatedElement->id];
            $correntFieldValue = array_values(array_unique(array_merge($oldElement->fields['stringMultipleField'], $additionalValues)));
            $this->assertEquals($updatedElement->fields['stringMultipleField'], $correntFieldValue);
        }

        $schema->delete();
    }

    /**
     * @group bulk
     */
    public function test_bulk_array_remove_updates()
    {
        $excessValues = ['0', '1'];
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringMultipleField',
                'type' => SchemaFieldTypes::STRING,
                'multiple' => true
            ]
        ]);

        for ($i = 0; $i < 5; $i++) {
            $field = [];

            for ($j = $i; $j < 5; $j++) {
                $field[] = (string) $j;
            }

            $element = Element::create($schema->id, ['stringMultipleField' => $field], $this->user->backend);
            $elements[$element->id] = $element;
        }

        Element::bulkUpdate($schema, array_keys($elements), ['stringMultipleField' => [
            'action' => 'remove',
            'value' => $excessValues
        ]], $this->user->backend);

        $newElements = Element::list($schema, $this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => array_keys($elements)
                ]
            ]
        ]);

        foreach ($newElements as $updatedElement) {
            $oldElement = $elements[$updatedElement->id];
            $correntFieldValue = array_values(array_diff($oldElement->fields['stringMultipleField'], $excessValues));
            sort($updatedElement->fields['stringMultipleField']);
            $this->assertEquals($updatedElement->fields['stringMultipleField'], $correntFieldValue);
        }

        $schema->delete();
    }
}
