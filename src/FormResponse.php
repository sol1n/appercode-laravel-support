<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\FormResponse as FormResponseContract;

use Appercode\Exceptions\FormResponse\CreateException;
use Appercode\Exceptions\FormResponse\ReceiveException;

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
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/' . $data['form'] . '/submit?submit=true'
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

    public static function create(array $fields, string $formId, Backend $backend): FormResponseContract
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

            $method = self::methods($backend, 'create', ['form' => $formId]);
            $id = (string) self::request([
                'method' => $method['type'],
                'json' => $fields,
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

            throw new CreateException($message, $code, $e, ['fields' => $fields]);
        }
    }

    public static function find(Backend $backend, string $id): FormResponseContract
    {
        return self::list($backend, [
            'where' => [
                'id' => $id
            ]
        ])->first();
    }

    public static function list(Backend $backend, $filter = null): Collection
    {
        $method = self::methods($backend, 'list');

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $filter ?? (object)[],
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
}
