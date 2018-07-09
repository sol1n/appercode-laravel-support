<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

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

            foreach ($element->fields as $fieldName => $fieldValue) {
                $this->assertEquals($fieldValue, $elementFields[$fieldName]);
            }

            $schema->delete();
        }
    }
}
