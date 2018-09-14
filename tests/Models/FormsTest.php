<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Helpers\FormCreator;

use Appercode\Form;
use Appercode\User;
use Appercode\Backend;

use Appercode\Enums\Form\Types as FormTypes;

use Appercode\Exceptions\Form\CreateException;

use Carbon\Carbon;

class FormsTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
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
            'welcomePart' => FormCreator::part('welcomePart', ['textBox']),
            'resultPart' => FormCreator::part('resultPart', ['textBox']),
            'parts' => [
                FormCreator::part('somePart', ['textBox', 'multilineTextBox', 'numberInput', 'floatInput', 'dateTimePicker']),
                FormCreator::part('someAnotherPart', ['textBlock'])
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



    public function test_form_can_be_counted()
    {
        $form = Form::create($this->formData(), $this->user->backend);

        $formsCount = Form::count($this->user->backend, [
            'where' => [
                'id' => $form->id
            ]
        ]);

        $this->assertEquals($formsCount, 1);

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

    public function test_forms_can_be_sorted_by_dates()
    {
        $formData = $this->formData();
        
        $formData['openAt'] = (new Carbon)->setTimezone('Europe/Moscow')->toAtomString();
        $formData['closeAt'] = (new Carbon)->setTimezone('Europe/Moscow')->toAtomString();
        $first = Form::create($formData, $this->user->backend);

        $formData['openAt'] = (new Carbon)->addDay()->setTimezone('Europe/Moscow')->toAtomString();
        $formData['closeAt'] = (new Carbon)->addDay()->setTimezone('Europe/Moscow')->toAtomString();
        $last = Form::create($formData, $this->user->backend);

        $ids = [$first->id, $last->id];
        $filter = [
            'where' => [
                'id' => [
                    '$in' => $ids
                ]
            ]
        ];

        $filter['order']['openAt'] = 'asc';
        $formsList = Form::list($this->user->backend, $filter);
        $this->assertEquals($formsList->first()->id, $first->id);

        $filter['order']['openAt'] = 'desc';
        $formsList = Form::list($this->user->backend, $filter);
        $this->assertEquals($formsList->first()->id, $last->id);

        $filter['order'] = ['closeAt' => 'asc'];
        $formsList = Form::list($this->user->backend, $filter);
        $this->assertEquals($formsList->first()->id, $first->id);

        $filter['order']['closeAt'] = 'desc';
        $formsList = Form::list($this->user->backend, $filter);
        $this->assertEquals($formsList->first()->id, $last->id);
    }

    public function test_forms_can_be_created_with_option_controls()
    {
        $formData = $this->formData();
        $formData['parts'] = [
            FormCreator::part('somePart', ['checkBox', 'checkBoxList', 'radioButtons', 'comboBox']),
            FormCreator::part('someAnotherPart', ['imagesCheckBoxList', 'ratingInput'])
        ];

        $form = Form::create($formData, $this->user->backend);

        foreach ($form->parts as $partIndex => $part) {
            $sourcePart = $formData['parts'][$partIndex];
            $this->assertEquals(count($sourcePart['sections'][0]['groups'][0]['controls']), count($part['sections'][0]['groups'][0]['controls']));

            foreach ($part['sections'][0]['groups'][0]['controls'] as $controlIndex => $control) {
                $sourceControl = $sourcePart['sections'][0]['groups'][0]['controls'][$controlIndex];

                $this->assertEquals($control['options']['$type'], 'Option2[]');
                $this->assertEquals($control['options']['value'], $sourceControl['options']);
            }
        }

        $form->delete();
    }
}
