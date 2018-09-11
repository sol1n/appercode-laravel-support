<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

use Appercode\Exceptions\Element\SaveException;
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

    protected function tearDown()
    {
        try {
            $schema = Schema::find('elementsTestSchema', $this->user->backend);
            $schema->delete();
        } catch (\Exception $e) {
        }
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
    }

    public function test_element_can_be_recivied_from_list_method()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        $element = Element::create($schema->id, ['stringSingleField' => 'title'], $this->user->backend);

        $elements = Element::list($schema->id, $this->user->backend);
        $this->assertEquals($element->id, $elements->first()->id);
    }

    public function test_elements_list_method_throws_correct_exception()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);

        Element::create($schema->id, ['stringSingleField' => 'title'], $this->user->backend);

        $this->expectException(ReceiveException::class);

        Element::list($schema, $this->user->backend, [
            'where' => [
                'id' => [
                    'wrong array value'
                ]
            ]
        ]);
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
        $element->delete();

        $this->expectException(ReceiveException::class);
        Element::find($schema, $element->id, $this->user->backend);
    }

    public function test_element_saving_test()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ],
            [
                'name' => 'stringMultipleField',
                'type' => SchemaFieldTypes::STRING,
                'multiple' => true,
            ],
            [
                'name' => 'integerSingleField',
                'type' => SchemaFieldTypes::INTEGER
            ],
            [
                'name' => 'textSingleField',
                'type' => SchemaFieldTypes::TEXT
            ]
        ]);

        $element = Element::create($schema, [
            'stringSingleField' => 'stringSingleField',
            'stringMultipleField' => [
                'stringMultipleField1',
                'stringMultipleField2',
            ],
            'integerSingleField' => 1,
            'textSingleField' => 'textSingleField'
        ], $this->user->backend);

        $element->fields['stringSingleField'] = 'stringSingleField2';
        $element->fields['stringMultipleField'] = [
            'stringMultipleField3',
            'stringMultipleField4'
        ];
        $element->fields['integerSingleField'] = 2;
        $element->fields['textSingleField'] = 'textSingleField2';

        $element->save();

        $newElement = Element::find($schema, $element->id, $this->user->backend);

        $this->assertEquals($element->id, $newElement->id);
        $this->assertEquals($element->fields, $newElement->fields);
    }

    public function test_element_can_be_updated_with_static_method()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ],
            [
                'name' => 'stringMultipleField',
                'type' => SchemaFieldTypes::STRING,
                'multiple' => true,
            ],
            [
                'name' => 'integerSingleField',
                'type' => SchemaFieldTypes::INTEGER
            ],
            [
                'name' => 'textSingleField',
                'type' => SchemaFieldTypes::TEXT
            ]
        ]);
        
        $element = Element::create($schema, [
            'stringSingleField' => 'stringSingleField',
            'stringMultipleField' => [
                'stringMultipleField1',
                'stringMultipleField2',
            ],
            'integerSingleField' => 1,
            'textSingleField' => 'textSingleField'
        ], $this->user->backend);

        $newData = [
            'stringSingleField' => 'stringSingleField2',
            'stringMultipleField' => [
                'stringMultipleField3',
                'stringMultipleField4'
            ],
            'integerSingleField' => 2,
            'textSingleField' => 'textSingleField2'
        ];

        Element::update($schema, $element->id, $newData, $this->user->backend);

        $newElement = Element::find($schema, $element->id, $this->user->backend);

        $this->assertArraySubset($newData, $newElement->fields);
    }

    public function test_element_save_method_throws_correct_exception()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'integerSingleField',
                'type' => SchemaFieldTypes::INTEGER
            ]
        ]);
        
        $element = Element::create($schema, [
            'integerSingleField' => 1
        ], $this->user->backend);

        $this->expectException(SaveException::class);

        $element->fields['integerSingleField'] = 'wrong value';
        $element->save();
    }

    public function test_element_can_recieve_lang_data()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING,
                'localized' => true
            ],
            [
                'name' => 'integerSingleField',
                'type' => SchemaFieldTypes::INTEGER,
                'localized' => true
            ]
        ]);

        $element = Element::create($schema, [
            'stringSingleField' => 'ruValue',
            'integerSingleField' => 1
        ], $this->user->backend);

        Element::updateLanguages($schema, $element->id, [
            'en' => [
                'stringSingleField' => 'enValue',
                'integerSingleField' => 2
            ]
        ], $this->user->backend);

        $newElement = Element::find($schema, $element->id, $this->user->backend)->getLanguages('en');

        $this->assertEquals($newElement->fields['integerSingleField'], 1);
        $this->assertEquals($newElement->fields['stringSingleField'], 'ruValue');
        $this->assertEquals($newElement->languages['en']['integerSingleField'], 2);
        $this->assertEquals($newElement->languages['en']['stringSingleField'], 'enValue');
    }

    public function test_element_list_method_can_returns_localized_fields()
    {
        $schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING,
                'localized' => true
            ],
            [
                'name' => 'integerSingleField',
                'type' => SchemaFieldTypes::INTEGER,
                'localized' => true
            ]
        ]);

        $element = Element::create($schema, [
            'stringSingleField' => 'ruValue',
            'integerSingleField' => 1
        ], $this->user->backend);

        Element::updateLanguages($schema, $element->id, [
            'en' => [
                'stringSingleField' => 'enValue',
                'integerSingleField' => 2
            ]
        ], $this->user->backend);

        $newElement = Element::list($schema, $this->user->backend, [
            'take' => -1
        ], ['en'])->first();

        $this->assertEquals($newElement->fields['integerSingleField'], 1);
        $this->assertEquals($newElement->fields['stringSingleField'], 'ruValue');
        $this->assertEquals($newElement->languages['en']['integerSingleField'], 2);
        $this->assertEquals($newElement->languages['en']['stringSingleField'], 'enValue');
    }
}
