<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Helpers\FormCreator;

use Appercode\User;
use Appercode\Form;
use Appercode\Backend;
use Appercode\FormResponse;

use Appercode\Enums\Form\Types as FormTypes;

use Carbon\Carbon;

class FormsResponseTest extends TestCase
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

    protected function templateAnswers()
    {
        return [
            'textBlock' => 'text Answer',
            'dateTimePicker' => (new Carbon)->setTimezone('Europe/Moscow')->toAtomString(),
            'floatInput' => 2.55,
            'numberInput' => 5,
            'multilineTextBox' => 'text Answer',
            'textBox' => 'text Answer',
            'checkBox' => 1,
            'checkBoxList' => [1],
            'radioButtons' => 1,
            'comboBox' => 1,
            'imagesCheckBoxList' => [1],
            'ratingInput' => 1,
        ];
    }

    public function test_form_response_can_be_created()
    {
        $form = Form::create($this->formData(), $this->user->backend);

        $questions = $form->questions();
        $templates = $this->templateAnswers();

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question['id']] = $templates[$question['type']];
        }

        $response = FormResponse::create($answers, $form->id, $this->user->backend);

        $this->assertEquals($response->formId, $form->id);

        $form->delete();
    }

    public function test_responses_can_be_recieved_by_list_method()
    {
        $form = Form::create($this->formData(), $this->user->backend);

        $questions = $form->questions();
        $templates = $this->templateAnswers();

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question['id']] = $templates[$question['type']];
        }

        FormResponse::create($answers, $form->id, $this->user->backend);
        FormResponse::create($answers, $form->id, $this->user->backend);
        FormResponse::create($answers, $form->id, $this->user->backend, true);

        $responses = FormResponse::list($this->user->backend, [
            'where' => [
                'formId' => $form->id,
                'userId' => $this->user->id
            ]
        ]);

        $this->assertEquals($responses->count(), 3);
        
        $responses = FormResponse::list($this->user->backend, [
            'where' => [
                'formId' => $form->id,
                'userId' => $this->user->id,
                'submittedAt' => [
                    '$exists' => false
                ]
            ]
        ]);

        $this->assertEquals($responses->count(), 1);

        $form->delete();
    }

    public function test_responses_can_be_counted()
    {
        $form = Form::create($this->formData(), $this->user->backend);

        $questions = $form->questions();
        $templates = $this->templateAnswers();

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question['id']] = $templates[$question['type']];
        }

        FormResponse::create($answers, $form->id, $this->user->backend);

        $responsesCount = FormResponse::count($this->user->backend, [
            'where' => [
                'formId' => $form->id
            ]
        ]);

        $this->assertEquals($responsesCount, 1);

        $form->delete();
    }

    public function test_responses_can_be_deleted()
    {
        $form = Form::create($this->formData(), $this->user->backend);

        $questions = $form->questions();
        $templates = $this->templateAnswers();

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question['id']] = $templates[$question['type']];
        }

        $responses[] = FormResponse::create($answers, $form->id, $this->user->backend);
        $responses[] = FormResponse::create($answers, $form->id, $this->user->backend);
        $responses[] = FormResponse::create($answers, $form->id, $this->user->backend, true);

        foreach ($responses as $response) {
            $response->delete();
        }

        $responsesCount = FormResponse::count($this->user->backend, [
            'where' => [
                'formId' => $form->id
            ]
        ]);

        $this->assertEquals($responsesCount, 0);

        $form->delete();
    }

    public function not_test_response_calculate_correct_answers()
    {
        $questionTypes = ['checkBox', 'checkBoxList', 'radioButtons', 'comboBox', 'imagesCheckBoxList'];

        foreach ($questionTypes as $questionType) {
            $formData = $this->formData();
            $formData['parts'] = [
                FormCreator::part('somePart', [$questionType]),
            ];

            $form = Form::create($formData, $this->user->backend);
            $questions = $form->questions();

            $answers = [];
            $templates = $this->templateAnswers();
            foreach ($questions as $question) {
                $answers[$question['id']] = $templates[$question['type']];
            }

            $response = FormResponse::create($answers, $form->id, $this->user->backend);

            $this->assertEquals($response->correctCount, 1);

            $form->delete();
        }
    }

    public function test_response_can_compile_member_answer_with_correctness()
    {
        $formData = $this->formData();
        $formData['parts'] = [
            FormCreator::part('somePart', ['textBox', 'multilineTextBox', 'numberInput', 'floatInput', 'dateTimePicker']),
            FormCreator::part('somePart', ['checkBox', 'checkBoxList', 'radioButtons', 'comboBox', 'imagesCheckBoxList']),
        ];

        $form = Form::create($formData, $this->user->backend);
        $questions = $form->questions();

        $answers = [];
        $templates = $this->templateAnswers();
        foreach ($questions as $question) {
            $answers[$question['id']] = $templates[$question['type']];
        }

        $response = FormResponse::create($answers, $form->id, $this->user->backend);

        $memberAnswers = $response->memberAnswers();

        foreach ($answers as $controlId => $answer) {
            $this->assertEquals($answer, $memberAnswers['answers'][$controlId]['value']);
        }

        $form->delete();
    }
}
