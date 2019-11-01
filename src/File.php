<?php

namespace Appercode;

use Appercode\Backend;

use Appercode\Traits\AppercodeRequest;

class File
{
    use AppercodeRequest;

    public $id;
    public $name;
    public $ownerId;
    public $ownerName;
    public $parentId;
    public $fileType;
    public $shareStatus;
    public $createdAt;
    public $updatedAt;
    public $isDeleted;
    public $rights;
    public $length;

    public $backend;

    public function __construct($json, $backend)
    {
        $this->backend = $backend;

        $this->id = $json['id'];
        $this->parentId = $json['parentId'];
        $this->ownerId = $json['ownerId'];
        $this->name = $json['name'];
        $this->length = $json['length'];
    }

    public static function create($props, Backend $backend)
    {
        $data = self::jsonRequest([
            'method' => 'POST',
            'json' => $props,
            'headers' => ['X-Appercode-Session-Token' => $backend->token()],
            'url' => $backend->server . $backend->project . '/files'
        ]);

        return new File($data, $backend);
    }

    public function upload($multipart)
    {
        try {
            self::request([
                'method' => 'POST',
                'multipart' => $multipart,
                'headers' => [
                    'X-Appercode-Session-Token' => $this->backend->token(),
                    'Accept' => 'application/json'
                ],
                'url' => $this->backend->server . $this->backend->project. '/files/' . $this->id . '/upload'
            ]);
        } catch (\Exception $e) {
            dd($e);
        }

        return $this;
    }
}
