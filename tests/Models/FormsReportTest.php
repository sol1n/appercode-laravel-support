<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Helpers\FormCreator;

use Appercode\User;
use Appercode\Form;
use Appercode\Backend;
use Appercode\FormReport;
use Appercode\FormResponse;
use Appercode\Services\FormManager;

use Appercode\Enums\Form\Types as FormTypes;

use Carbon\Carbon;

class FormsReportTest extends TestCase
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
            'isPublished' => true,
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

    public function test_report_can_be_created()
    {
        $questionsCount = 15;
        $formData = $this->formData();
        $formData['parts'] = [
            FormCreator::part('somePart', ['checkBoxList', 'radioButtons', 'comboBox', 'imagesCheckBoxList']),
        ];

        $form = Form::create($formData, $this->user->backend);
        $questions = $form->questions();
        $responses = [];

        for ($i = 0; $i < $questionsCount; $i++) {
            $answers = [];
            foreach ($questions as $question) {
                $answers[$question['id']] = in_array($question['type'], ['checkBoxList', 'imagesCheckBoxList'])
                    ? [rand(0, 2), rand(0, 2)]
                    : rand(0, 2);
            }

            if ($i == 0) {
                array_pop($answers);
            }

            $responses[] = FormResponse::create($answers, $form->id, $this->user->backend);
        }
        
        $controlsIds = $questions->map(function ($item) {
            return $item['id'];
        })->values()->toArray();

        $report = FormReport::create($this->user->backend, $form->id, $controlsIds);

        $results = $report->compiledResults();

        $this->assertEquals($results['form'], $form);
        foreach ($results['statistics'] as $controlStatistics) {
            $this->assertEquals($controlStatistics['count'], $questionsCount);
        }

        $form->delete();
    }

    public function test_report_can_be_listed()
    {
        $formData = $this->formData();
        $formData['parts'] = [
            FormCreator::part('somePart', ['checkBoxList', 'radioButtons', 'comboBox', 'imagesCheckBoxList']),
        ];

        $form = Form::create($formData, $this->user->backend);
        
        $controlsIds = $form->questions()->map(function ($item) {
            return $item['id'];
        })->values()->toArray();

        $createdReport = FormReport::create($this->user->backend, $form->id, $controlsIds);

        $reports = FormReport::list($this->user->backend, [
            'where' => [
                'formId' => $form->id
            ]
        ]);

        $this->assertEquals($createdReport->id, $reports->first()->id);

        $form->delete();
    }

    public function test_form_can_create_report()
    {
        $formData = $this->formData();
        $formData['parts'] = [
            FormCreator::part('somePart', ['textBox', 'radioButtons', 'comboBox', 'imagesCheckBoxList']),
            FormCreator::part('someAnotherPart', ['checkBoxList', 'radioButtons', 'floatInput', 'imagesCheckBoxList']),
        ];

        $form = Form::create($formData, $this->user->backend);

        $report = $form->reportsManager->recreateVariantsReport();

        $this->assertEquals(count($report->perspectives), 6);

        $form->delete();
    }

    public function test_form_report_can_be_deleted()
    {
        $formData = $this->formData();
        $formData['parts'] = [
            FormCreator::part('somePart', ['textBox', 'radioButtons', 'comboBox', 'imagesCheckBoxList']),
            FormCreator::part('someAnotherPart', ['checkBoxList', 'radioButtons', 'floatInput', 'imagesCheckBoxList']),
        ];

        $form = Form::create($formData, $this->user->backend);

        $controlsIds = $form->questions()->map(function ($item) {
            return $item['id'];
        })->values()->toArray();

        $report = FormReport::create($this->user->backend, $form->id, $controlsIds);

        $report->delete();

        $reports = FormReport::list($this->user->backend, [
            'where' => [
                'formId' => $form->id
            ]
        ]);

        $this->assertEquals($reports->count(), 0);

        $form->delete();
    }
}
