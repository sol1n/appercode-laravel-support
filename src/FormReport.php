<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\FormReport as FormReportContract;

use Appercode\Form;

use Appercode\Exceptions\FormReport\CreateException;
use Appercode\Exceptions\FormReport\ReceiveException;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class FormReport implements FormReportContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $formId;

    public $isPublished;
    public $isDeleted;

    public $createdAt;
    public $updatedAt;

    public $updatedBy;

    public $perspectives;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/reports'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/reports/query'
                ];
            case 'results':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/v2/forms/reports/' . $data['id'] . '/result'
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(Backend $backend, array $data)
    {
        $this->id = $data['id'];
        $this->formId = $data['formId'];
        $this->isPublished = $data['isPublished'];
        $this->isDeleted = $data['isDeleted'];

        $this->createdAt = is_null($data['createdAt'])
            ? null
            : Carbon::parse($data['createdAt']);
        $this->updatedAt = is_null($data['updatedAt'])
            ? null
            : Carbon::parse($data['updatedAt']);
        $this->updatedBy = $data['updatedBy'] ?? null;

        $this->perspectives = $data['perspectives'] ?? null;

        $this->backend = $backend;
    }

    public static function create(Backend $backend, string $formId, array $controlsIds): FormReportContract
    {
        $method = self::methods($backend, 'create');

        $data = [
            'formId' => $formId
        ];

        foreach ($controlsIds as $id) {
            $data['perspectives'][] = [
                'controlId' => $id
            ];
        }

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $data,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, ['formId' => $formId, 'controlsIds' => $controlsIds]);
        }

        return new self($backend, $json);
    }

    public static function list(Backend $backend, array $filter = []): Collection
    {
        $method = self::methods($backend, 'list');
        try {
            return collect(self::jsonRequest([
                'method' => $method['type'],
                'json' => (object) $filter,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]))->map(function ($item) use ($backend) {
                return new self($backend, $item);
            });
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['filter' => $filter]);
        }
    }

    public function results(): array
    {
        $method = self::methods($this->backend, 'results', ['id' => $this->id]);

        try {
            return self::jsonRequest([
                'method' => $method['type'],
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new ReceiveException($message, $code, $e, ['id' => $this->id]);
        }
    }

    public function form()
    {
        return Form::list($this->backend, [
            'where' => [
                'id' => $this->formId
            ]
        ])->first();
    }

    public function compiledResults(): array
    {
        $results = [];
        $data = $this->results();
        $form = Form::list($this->backend, [
            'where' => [
                'id' => $this->formId
            ]
        ])->first();

        $questions = $form->questions();

        foreach ($data as $controlStatistics) {
            $variants = [];
            $controlId = $controlStatistics['controlId'];
            $question = $questions[$controlId];

            foreach ($controlStatistics['values'] as $option) {
                if (is_array($option['value'])) {
                    $option['value'] = array_unique($option['value']);
                    foreach ($option['value'] as $optionValue) {
                        if (isset($variants[$optionValue])) {
                            $variants[$optionValue]['count'] += $option['count'];
                            if (isset($option['responses']) && is_array($option['responses'])) {
                                $variants[$optionValue]['responses'] = array_merge($variants[$optionValue]['responses'], $option['responses']);
                            }
                        } else {
                            $variants[$optionValue]['count'] = $option['count'];
                            if (isset($option['responses']) && is_array($option['responses'])) {
                                $variants[$optionValue]['responses'] = $option['responses'];
                            } else {
                                $variants[$optionValue]['responses'] = [];
                            }
                            $variants[$optionValue]['isCorrect'] = in_array($optionValue, $question['correctValues']);
                        }
                    }
                } else {
                    $optionValue = $option['value'];
                    $variants[$optionValue]['count'] = $option['count'];
                    if (isset($option['responses']) && is_array($option['responses'])) {
                        $variants[$optionValue]['responses'] = $option['responses'];
                    } else {
                        $variants[$optionValue]['responses'] = [];
                    }
                    $variants[$optionValue]['isCorrect'] = in_array($optionValue, $question['correctValues']);
                }

                foreach ($variants as $optionValue => $optionData) {
                    if ($controlStatistics['count']) {
                        $variants[$optionValue]['popularity'] = round($optionData['count'] / $controlStatistics['count'], 3);
                    } else {
                        $variants[$optionValue]['popularity'] = 0;
                    }
                }
            }
            
            ksort($variants);

            $results['statistics'][$controlId] = [
                'id' => $controlId,
                'type' => $question['type'],
                'title' => $question['title'],
                'description' => $question['description'],
                'viewData' => $question['viewData'],
                'options' => $variants,
                'count' => $controlStatistics['count'],
                'correctValues' => $question['correctValues']
            ];
        }

        $results['form'] = $form;

        return $results;
    }
}
