<?php

namespace Appercode\Contracts\Services;

use Appercode\Contracts\Element;

use Appercode\User;

use Illuminate\Support\Collection;

interface ViewsManager
{
    public function getBadges($schema): Collection;
    public function views($schema, array $elementIds = []): Collection;

    public function sendView(User $user, Element $element): void;

    public function addBadges($schema, array $map): void;
    public function removeBadges($schema, array $map): void;
    public function putBadges($schema, array $changes): void;
}
