<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

use Appercode\Exceptions\Element\ReceiveException;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class ElementsTest extends TestCase
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

    public function test_create_element_with_simple_type_fields()
    {
        $data = [
            SchemaFieldTypes::STRING => [
                'single' => [
                    'stringSingleField' => 'stringValue'
                ],
                'multiple' => [
                    'stringMultipleField' => [
                        'stringValue1',
                        'stringValue2'
                    ]
                ]
            ],
            SchemaFieldTypes::INTEGER => [
                'single' => [
                    'integerSingleField' => 0
                ],
                'multiple' => [
                    'integerMultipleField' => [0, 1, 2]
                ]
            ],
            SchemaFieldTypes::DOUBLE => [
                'single' => [
                    'doubleSingleField' => 0.2
                ],
                'multiple' => [
                    'doubleMultipleField' => [0, 1.5, 2.55]
                ]
            ],
            SchemaFieldTypes::MONEY => [
                'single' => [
                    'moneySingleField' => 0.2
                ],
                'multiple' => [
                    'moneyMultipleField' => [0, 1.5, 2.55]
                ]
            ],
            SchemaFieldTypes::DATETIME => [
                'single' => [
                    'dateTimeSingleField' => (new Carbon)->setTimezone('Europe/Moscow')->toAtomString()
                ],
                'multiple' => [
                    'dateTimeMultipleField' => [
                        (new Carbon)->addDay()->setTimezone('Europe/Moscow')->toAtomString(),
                        (new Carbon)->setTimezone('Europe/Moscow')->toAtomString(),
                        (new Carbon)->subDay()->setTimezone('Europe/Moscow')->toAtomString()
                    ]
                ]
            ],
            SchemaFieldTypes::BOOLEAN => [
                'single' => [
                    'booleanSingleField' => true
                ],
                'multiple' => [
                    'booleanMultipleField' => [true, false, true]
                ]
            ],
            SchemaFieldTypes::TEXT => [
                'single' => [
                    'textSingleField' => 'stringValue'
                ],
                'multiple' => [
                    'textMultipleField' => [
                        'stringValue1',
                        '<b>stringValue2</b>'
                    ]
                ]
            ],
            SchemaFieldTypes::UUID => [
                'single' => [
                    'uuidSingleField' => Uuid::uuid1()->toString()
                ],
                'multiple' => [
                    'uuidMultipleField' => [
                        Uuid::uuid1()->toString(),
                        Uuid::uuid1()->toString()
                    ]
                ]
            ]
        ];

        foreach ($data as $currentIndex => $currentType) {
            $schemaFields = $elementFields = [];
            foreach ($data as $previousIndex => $previousType) {
                $elementFields = array_merge($previousType['single'], $elementFields);
                $elementFields = array_merge($previousType['multiple'], $elementFields);
                $schemaFields[] = [
                    'name' => array_keys($previousType['single'])[0],
                    'type' => $previousIndex
                ];
                $schemaFields[] = [
                    'name' => array_keys($previousType['multiple'])[0],
                    'type' => '[' . $previousIndex . ']'
                ];
                if ($currentIndex == $previousIndex) {
                    break;
                }
            }

            $schema = $this->createSchema($this->user->backend, $schemaFields);
            $element = Element::create($schema->id, $elementFields, $this->user->backend);

            $elementFields['isPublished'] = true;

            foreach ($element->fields as $fieldName => $fieldValue) {
                $this->assertEquals($fieldValue, $elementFields[$fieldName]);
            }

            $schema->delete();
        }
    }

    public function test_element_can_be_recivied_from_find_method()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        $element = Element::create($schema->id, ['stringSingleField' => 'title'], $this->user->backend);

        $newElement = Element::find($schema->id, $element->id, $this->user->backend);
        $this->assertEquals($element->id, $newElement->id);

        $schema->delete();
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

        Element::update(array_keys($elements), ['stringSingleField' => 'new-title'], $schema, $this->user->backend);

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

        Element::update(array_keys($elements), ['stringMultipleField' => [
            'action' => 'append',
            'value' => $additionalValues
        ]], $schema, $this->user->backend);

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

        Element::update(array_keys($elements), ['stringMultipleField' => [
            'action' => 'remove',
            'value' => $excessValues
        ]], $schema, $this->user->backend);

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

    public function test_element_can_be_deleted()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        $element = Element::create($schema->id, ['stringSingleField' => 'title'], $this->user->backend);
        $element->delete();

        $elements = Element::list($schema, $this->user->backend);
        $this->assertEquals($elements->count(), 0);

        $schema->delete();
    }

    public function test_deleted_element_receiving_throws_correct_exception()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        $element = Element::create($schema->id, ['stringSingleField' => 'title'], $this->user->backend);
        $schema->delete();

        $this->expectException(ReceiveException::class);
        Element::find($schema, $element->id, $this->user->backend);
    }

    /**
     * @group bulk
     */
    public function test_elements_can_be_deleted_with_bulk_request()
    {
        $elements = [];
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        for ($i = 0; $i < 5; $i++) {
            $element = Element::create($schema->id, ['stringSingleField' => 'test'], $this->user->backend);
            $elements[$element->id] = $element;
        }

        Element::bulkDelete($schema->id, array_keys($elements), $this->user->backend);

        $elements = Element::list($schema, $this->user->backend);
        $this->assertEquals($elements->count(), 0);

        $schema->delete();
    }
}
