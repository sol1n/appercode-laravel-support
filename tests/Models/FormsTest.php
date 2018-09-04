<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\Form;
use Appercode\User;
use Appercode\Backend;

use Appercode\Enums\Form\Types as FormTypes;

use Appercode\Exceptions\Form\CreateException;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class FormsTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
    }

    protected function simpleControl($code, $type)
    {
        return [
            "title" => [
                "ru" => "$code $type title",
                "en" => "$code $type title en"
            ],
            "description" => [
                "ru" => "$code $type description",
                "en" => "$code $type description en"
            ],
            "placeholder" => [
                "ru" => "$code $type placeholder",
                "en" => "$code $type placeholder en"
            ],
            "options" => null,
            "class" => "$code textBox control class",
            "id" => "id" . rand(1000, 9999),
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "validateConditions" => [
                [
                    "booleanExpression" => [
                        "someId" => [
                            '$in' => [1,2,3]
                        ]
                    ],
                    "errorMessage" => "string"
                ]
            ],
            "type" => "$type",
            "viewData" => [
                "key" => "value"
            ],
            "correctValues" => null
        ];
    }

    protected function listControl($code, $type)
    {
        return [
            "title" => [
                "ru" => "$code $type title",
                "en" => "$code $type title en"
            ],
            "description" => [
                "ru" => "$code $type description",
                "en" => "$code $type description en"
            ],
            "placeholder" => [
                "ru" => "$code $type placeholder",
                "en" => "$code $type placeholder en"
            ],
            "options" => [
                [
                    "title" => [
                        "ru" => "Option 1 title",
                        "en" => "Option 1 title en"
                    ],
                    "value" => 0,
                    "class" => "Option 1 class"
                ],
                [
                    "title" => [
                        "ru" => "Option 2 title",
                        "en" => "Option 2 title en"
                    ],
                    "value" => 1,
                    "class" => "Option 2 class",
                    "displayCondition" => [
                        "someId" => [
                            '$in' => [1,2,3]
                        ]
                    ]
                ]
            ],
            "class" => "$code textBox control class",
            "id" => "id" . rand(1000, 9999),
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "validateConditions" => [
                [
                    "booleanExpression" => [
                        "someId" => [
                            '$in' => [1,2,3]
                        ]
                    ],
                    "errorMessage" => "string"
                ]
            ],
            "type" => "$type",
            "viewData" => [
                "key" => "value"
            ],
            "correctValues" => null
        ];
    }

    protected function control($code, $type)
    {
        switch ($type) {
            case 'textBox':
            case 'multilineTextBox':
            case 'numberInput':
            case 'floatInput':
            case 'dateTimePicker':
            case 'textBlock':
                return $this->simpleControl($code, $type);
            case 'checkBox':
            case 'checkBoxList':
            case 'radioButtons':
            case 'comboBox':
            case 'ImagesCheckBoxList':
            case 'RatingInput':
                return $this->listControl($code, $type);
            default:
                return (object)[];
        }
    }

    protected function group($code, $questions)
    {
        $controls = [];
        foreach ($questions as $controlType) {
            $controls[] = $this->control($code, $controlType);
        }

        return [
            "controls" => $controls,
            "class" => "group-class",
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "viewData" => [
                "key" => "value"
            ]
        ];
    }

    protected function section($code, $questions = [])
    {
        return [
            "title" => [
                "ru" => "$code section title",
                "en" => "$code section title en"
            ],
            "description" => [
                "ru" => "$code section description",
                "en" => "$code section description en"
            ],
            "groups" => [
                $this->group($code, $questions)
            ],
            "class" => "section-class",
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "viewData" => [
                "key" => "value"
            ]
        ];
    }

    protected function part($code = '', $questions = [])
    {
        return [
            "title" => [
                "ru" => "$code title",
                "en" => "$code title en"
            ],
            "description" => [
                "ru" => "$code description",
                "en" => "$code description en"
            ],
            "sections" => [
                $this->section($code, $questions)
            ],
            "class" => "part-class",
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "viewData" => [
                "key" => "value"
            ]
        ];
    }

    protected function formData()
    {
        return [
            'title' => [
                'ru' => 'Form title',
                'en' => 'Form title en'
            ],
            'type' => FormTypes::QUIZ,
            'description' => [
                'ru' => 'Form description',
                'en' => 'Form description en'
            ],
            'timeLimit' => 150,
            'isResubmittingAllowed' => true,
            'viewData' => [
                'key' => 'value'
            ],
            'groupIds' => [
                '00000000-0000-0000-0000-000000000000'
            ],
            'isPublished' => false,
            'openAt' => (new Carbon)->subDay()->setTimezone('Europe/Moscow')->toAtomString(),
            'closeAt' => (new Carbon)->addDay()->setTimezone('Europe/Moscow')->toAtomString(),
            'welcomePart' => $this->part('welcomePart', ['textBox']),
            'resultPart' => $this->part('resultPart', ['textBox']),
            'parts' => [
                $this->part('somePart', ['textBox', 'multilineTextBox', 'numberInput', 'floatInput', 'dateTimePicker']),
                $this->part('someAnotherPart', ['textBlock'])
            ]
        ];
    }

    public function test_form_can_be_created()
    {
        $formData = $this->formData();
        $form = Form::create($formData, $this->user->backend);

        $this->assertEquals($form->title['ru'], $formData['title']['ru']);
        $this->assertEquals($form->title['en'], $formData['title']['en']);
        $this->assertEquals($form->description['ru'], $formData['description']['ru']);
        $this->assertEquals($form->description['en'], $formData['description']['en']);
        $this->assertEquals($form->type, $formData['type']);
        $this->assertEquals($form->timeLimit, $formData['timeLimit']);
        $this->assertEquals($form->isResubmittingAllowed, $formData['isResubmittingAllowed']);
        $this->assertEquals($form->viewData['key'], $formData['viewData']['key']);
        $this->assertEquals($form->groupIds, $formData['groupIds']);
        $this->assertEquals($form->isPublished, $formData['isPublished']);
        $this->assertEquals($form->openAt->toAtomString(), $formData['openAt']);
        $this->assertEquals($form->closeAt->toAtomString(), $formData['closeAt']);

        //welcome & results part
        $this->assertEquals($form->welcomePart, $formData['welcomePart']);
        $this->assertEquals($form->resultPart, $formData['resultPart']);
        $this->assertEquals($form->parts, $formData['parts']);

        $form->delete();
    }

    public function test_form_creation_throws_correct_exception()
    {
        $this->expectException(CreateException::class);

        $form = Form::create([
            'title' => 'wrong title format'
        ], $this->user->backend);
    }

    public function test_form_can_be_fetched_in_list()
    {
        $forms = [];
        $formData = $this->formData();
        for ($i = 0; $i < 1; $i++) {
            $form = Form::create($formData, $this->user->backend);
            $forms[$form->id] = $form;
        }

        $formList = Form::list($this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => array_keys($forms)
                ]
            ]
        ]);

        $this->assertEquals(count($forms), $formList->count());

        foreach ($formList as $form) {
            $this->assertEquals($form->title['ru'], $formData['title']['ru']);
            $this->assertEquals($form->title['en'], $formData['title']['en']);
            $this->assertEquals($form->description['ru'], $formData['description']['ru']);
            $this->assertEquals($form->description['en'], $formData['description']['en']);
            $this->assertEquals($form->type, $formData['type']);
            $this->assertEquals($form->timeLimit, $formData['timeLimit']);
            $this->assertEquals($form->isResubmittingAllowed, $formData['isResubmittingAllowed']);
            $this->assertEquals($form->viewData['key'], $formData['viewData']['key']);
            $this->assertEquals($form->groupIds, $formData['groupIds']);
            $this->assertEquals($form->isPublished, $formData['isPublished']);
            $this->assertEquals($form->openAt->toAtomString(), $formData['openAt']);
            $this->assertEquals($form->closeAt->toAtomString(), $formData['closeAt']);

            //welcome & results part
            $this->assertEquals($form->welcomePart, $formData['welcomePart']);
            $this->assertEquals($form->resultPart, $formData['resultPart']);
            $this->assertEquals($form->parts, $formData['parts']);
        }

        foreach ($forms as $form) {
            $form->delete();
        }
    }

    /**
     * @group current
     */
    public function test_form_can_be_filtered_by_type_and_dates()
    {
        $formData = $this->formData();
        $form = Form::create($formData, $this->user->backend);

        $formList = Form::list($this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => [$form->id]
                ],
                'type' => $formData['type']
            ]
        ]);

        $this->assertEquals($formList->count(), 1);
        $this->assertEquals($formList->first()->id, $form->id);

        $formList = Form::list($this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => [$form->id]
                ],
                'openAt' => [
                    '$gte' => (new Carbon($formData['openAt']))->subDay()->setTimezone('Europe/Moscow')->toAtomString(),
                    '$lte' => (new Carbon($formData['openAt']))->addDay()->setTimezone('Europe/Moscow')->toAtomString(),
                ]
            ]
        ]);

        $this->assertEquals($formList->count(), 1);
        $this->assertEquals($formList->first()->id, $form->id);

        $formList = Form::list($this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => [$form->id]
                ],
                'closeAt' => [
                    '$gte' => (new Carbon($formData['closeAt']))->subDay()->setTimezone('Europe/Moscow')->toAtomString(),
                    '$lte' => (new Carbon($formData['closeAt']))->addDay()->setTimezone('Europe/Moscow')->toAtomString(),
                ]
            ]
        ]);

        $this->assertEquals($formList->count(), 1);
        $this->assertEquals($formList->first()->id, $form->id);
    }
}
