<?php

namespace Appercode\Onboarding;

use Appercode\Onboarding\Entity;

use Appercode\Enums\Onboarding\Task\ConfirmationTypes;

use Appercode\Contracts\Backend;
use Appercode\Contracts\Onboarding\Task as TaskContract;

use Illuminate\Support\Collection;

class Task extends Entity implements TaskContract
{
    /**
     * Rewards for task performance (points or achievement)
     * @var array
     */
    public $reward;

    /**
     * Enum of byForm, byPerformer, byMentor, byAdministrator
     * @var string
     */
    public $confirmationType;

    /**
     * Appercode/Form id, using if confirmation type is "byForm"
     * @var string
     */
    public $confirmationFormId;

    /**
     * Full html description
     * @var string
     */
    public $description;

    /**
     * Appercode\File image id
     * @var string
     */
    public $imageFileId;

    /**
     * Short description (300 symbols max)
     * @var string
     */
    public $subtitle;

    /**
     * Title (40 symbols max)
     * @var string
     */
    public $title;

    /**
     * Confirmation types variants
     * @return Illuminate\Support\Collection
     */
    public static function confirmationTypes(): Collection
    {
        return ConfirmationTypes::list();
    }

    protected static function methods(Backend $backend, string $name, array $data = [])
    {
        switch ($name) {
            case 'create':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/tasks'
                ];
            case 'delete':
                return [
                    'type' => 'DELETE',
                    'url' => $backend->server . $backend->project . '/onboarding/tasks/' . $data['id']
                ];
            case 'get':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/onboarding/tasks/' . $data['id']
                ];
            case 'count':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/tasks/query?count=true'
                ];
            case 'list':
                return [
                    'type' => 'POST',
                    'url' => $backend->server . $backend->project . '/onboarding/tasks/query'
                ];
            case 'update':
                return [
                    'type' => 'PUT',
                    'url' => $backend->server . $backend->project . '/onboarding/tasks/' . $data['id']
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    public function __construct(array $data, Backend $backend)
    {
        parent::__construct($data, $backend);

        $this->reward = $data['reward'] ?? [];
        $this->confirmationType = $data['confirmationType'];
        $this->confirmationFormId = $data['confirmationFormId'] ?? null;

        $this->description = $data['description'] ?? null;
        $this->imageFileId = $data['imageFileId'] ?? null;
        $this->subtitle = $data['subtitle'] ?? null;
        $this->title = $data['title'] ?? null;

        return $this;
    }

    /**
     * Json data for sending into appercode methods
     * @return array
     */
    public function toJson(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'imageFileId' => $this->imageFileId,
            'description' => $this->description,
            'confirmationFormId' => $this->confirmationFormId,
            'confirmationType' => $this->confirmationType,
            'reward' => (object) $this->reward
        ];
    }
}
