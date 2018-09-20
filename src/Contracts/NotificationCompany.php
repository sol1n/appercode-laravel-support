<?php

namespace Appercode\Contracts;

use Appercode\Backend;
use Illuminate\Support\Collection;

interface NotificationCompany
{
    public static function list(Backend $backend, $filter = []): Collection;

    public static function create(Backend $backend, array $fields): NotificationCompany;
    public static function update(Backend $backend, array $fields, string $id): void;
    public static function find(Backend $backend, string $id): NotificationCompany;

    public static function sendStatic(Backend $backend, array $ids): void;
    public static function deleteStatic(Backend $backend, array $ids): void;

    public function save(): NotificationCompany;
    public function send(): NotificationCompany;
    public function delete(): NotificationCompany;
}
