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

    public function results()
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
}
