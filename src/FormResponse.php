<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\FormResponse as FormResponseContract;

use Appercode\Form;

use Appercode\Exceptions\FormResponse\CreateException;
use Appercode\Exceptions\FormResponse\ReceiveException;
use Appercode\Exceptions\FormResponse\DeleteException;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class FormResponse implements FormResponseContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $userId;
    public $formId;

    public $createdAt;
    public $updatedAt;
    public $startedAt;
    public $submittedAt;

    public $language;
    public $response;
    public $submittedCount;
    public $correctCount;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'submit':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/' . $data['form'] . '/submit?submit=true'
                ];
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/' . $data['form'] . '/submit'
                ];
            case 'startForm':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/' . $data['form'] . '/start'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/responses/query'
                ];
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/responses/query?count=true'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/v2/forms/responses/' . $data['id']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
        $this->userId = $data['userId'];
        $this->formId = $data['formId'];
        $this->language = $data['language'];
        $this->response = $data['response'];
        $this->submittedCount = $data['submittedCount'];
        $this->correctCount = $data['correctCount'];

        $this->createdAt = new Carbon($data['createdAt']) ?? null;
        $this->updatedAt = new Carbon($data['updatedAt']) ?? null;
        $this->startedAt = new Carbon($data['startedAt']) ?? null;
        $this->submittedAt = new Carbon($data['submittedAt']) ?? null;


        $this->backend = $backend;

        return $this;
    }

    /**
     * Creates response for selected form
     * @param  array   $answers  answer values keyed by control ids
     * @param  string  $formId
     * @param  Appercode\Backend $backend
     * @param  bool    $partial  if true sends answers without submiting form
     * @return Appercode\Contracts\FormResponseContract
     */
    public static function create(array $answers, string $formId, Backend $backend, $partial = false): FormResponseContract
    {
        try {
            $method = self::methods($backend, 'startForm', ['form' => $formId]);
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => (object) [],
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);

            $method = self::methods(
                $backend,
                $partial ? 'create' : 'submit',
                ['form' => $formId]
            );

            $id = (string) self::request([
                'method' => $method['type'],
                'json' => $answers,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token(),
                    'Accept' => 'application/json'
                ],
                'url' => $method['url'],
            ])->getBody();

            $id = str_replace('"', '', $id);

            return FormResponse::find($backend, $id);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, ['fields' => $answers]);
        }
    }

    /**
     * Returns response by id
     * @param  Appercode\Backend $backend
     * @param  string  $id
     * @return Appercode\Contracts\FormResponseContract
     */
    public static function find(Backend $backend, string $id): FormResponseContract
    {
        return self::list($backend, [
            'where' => [
                'id' => $id
            ]
        ])->first();
    }

    /**
     * Returns responses by query
     * @param  Appercode\Backend $backend
     * @param  array  $filter
     * @return Illuminate\Support\Collection
     */
    public static function list(Backend $backend, array $filter = []): Collection
    {
        $method = self::methods($backend, 'list');

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => (object) $filter,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['fields' => $filter]);
        }

        return collect($json)->map(function ($fields) use ($backend) {
            return new FormResponse($fields, $backend);
        });
    }

    /**
     * Returns responses count for provided filter
     * @param  Appercode\Backend $backend
     * @param  array  $filter
     * @return int
     */
    public static function count(Backend $backend, $filter = []): int
    {
        $method = self::methods($backend, 'count');

        try {
            return self::countRequest([
                'method' => $method['type'],
                'json' => (object) $filter,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['fields' => $filter]);
        }
    }

    public function delete(): FormResponseContract
    {
        $method = self::methods($this->backend, 'delete', ['id' => $this->id]);

        try {
            self::request([
                'method' => $method['type'],
                'json' => (object) [],
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new DeleteException($message, $code, $e);
        }

        return $this;
    }

    public function memberAnswers(): array
    {
        $result = [];

        $form = Form::list($this->backend, [
            'where' => [
                'id' => $this->formId
            ]
        ])->first();

        $questions = [];
        foreach ($form->questions() as $question) {
            $options = [];
            if (isset($question['options']['value']) && is_array($question['options']['value'])) {
                foreach ($question['options']['value'] as $option) {
                    $options[$option['value']] = [
                        'value' => $option['value'],
                        'title' => $option['title'],
                        'isCorrect' => isset($question['correctValues']) && is_array($question['correctValues']) && in_array($option['value'], $question['correctValues'])
                    ];
                }
            }

            $questions[$question['id']] = [
                'id' => $question['id'],
                'title' => $question['title'],
                'description' => $question['description'],
                'type' => $question['type'],
                'options' => count($options) ? $options : null
            ];
        }

        $answers = [];

        if (isset($this->response) && is_array($this->response)) {
            foreach ($this->response as $index => $response) {
                if (!is_null($response)) {
                    $question = $questions[$index];
                    switch ($question['type']) {
                        case 'checkBox':
                        case 'checkBoxList':
                        case 'radioButtons':
                        case 'comboBox':
                        case 'imagesCheckBoxList':
                            if (is_array($response)) {
                                $options = [];
                                foreach ($response as $option) {
                                    $options[] = $question['options'][$option]['title'] ?? $option;
                                }
                                $displayValue = $options;
                            } else {
                                $displayValue = $question['options'][$response]['title'] ?? $response;
                            }
                            break;
                        case 'dateTimePicker':
                            $displayValue = (new Carbon($response))->format('d-m-Y');
                            break;
                        
                        default:
                            $displayValue = $response;
                            break;
                    }

                    $answers[$index] = [
                        'value' => $response,
                        'displayValue' => $displayValue
                    ];
                } else {
                    $answers[$index] = null;
                }
            }
        }

        return [
            'form' => $form,
            'questions' => $questions,
            'answers' => $answers
        ];
    }
}
