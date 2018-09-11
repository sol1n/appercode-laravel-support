<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\Form as FormContract;

use Appercode\Exceptions\Form\CreateException;
use Appercode\Exceptions\Form\DeleteException;
use Appercode\Exceptions\Form\ReceiveException;

use Appercode\Backend;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class Form implements FormContract
{
    use AppercodeRequest;

    private $backend;

    public $id;
    public $title;
    public $description;
    public $type;
    public $timeLimit;
    public $isResubmittingAllowed;

    public $parts;
    public $welcomePart;
    public $resultPart;

    public $viewData;
    public $groupIds;

    public $isDeleted;
    public $isPublished;

    public $createdAt;
    public $updatedAt;
    public $openAt;
    public $closeAt;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/v2/forms/query'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/v2/forms/' . $data['id']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        $this->id = $data['id'];
        $this->title = $data['title'] ?? [];
        $this->description = $data['description'] ?? [];
        $this->type = $data['type'] ?? '';
        $this->timeLimit = $data['timeLimit'] ?? null;
        $this->isResubmittingAllowed = $data['isResubmittingAllowed'] ?? null;
        $this->parts = $data['parts'] ?? [];
        $this->welcomePart = $data['welcomePart'] ?? null;
        $this->resultPart = $data['resultPart'] ?? null;
        $this->viewData = $data['viewData'] ?? [];
        $this->groupIds = $data['groupIds'] ?? [];
        $this->isDeleted = $data['isDeleted'] ?? false;
        $this->isPublished = $data['isPublished'] ?? true;
        $this->createdAt = new Carbon($data['createdAt']) ?? null;
        $this->updatedAt = new Carbon($data['updatedAt']) ?? null;
        $this->openAt = new Carbon($data['openAt']) ?? null;
        $this->closeAt = new Carbon($data['closeAt']) ?? null;

        $this->backend = $backend;

        return $this;
    }

    public static function create(array $fields, Backend $backend): FormContract
    {
        $method = self::methods($backend, 'create');

        try {
            $json = self::jsonRequest([
                'method' => $method['type'],
                'json' => $fields,
                'headers' => [
                    'X-Appercode-Session-Token' => $backend->token()
                ],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new CreateException($message, $code, $e, ['fields' => $fields]);
        }

        return new Form($json, $backend);
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
            return new Form($fields, $backend);
        });
    }

    public function delete(): FormContract
    {
        $method = self::methods($this->backend, 'delete', ['id' => $this->id]);

        try {
            self::request([
                'method' => $method['type'],
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

    public function controls(): Collection
    {
        $result = [];
        foreach ($this->parts as $part) {
            foreach ($part['sections'] as $section) {
                foreach ($section['groups'] as $group) {
                    $result = array_merge($result, $group['controls']);
                }
            }
        }

        return collect($result);
    }

    public function questions(): Collection
    {
        return $this->controls()->mapWithKeys(function ($item) {
            return [$item['id'] => $item];
        });
    }
}
