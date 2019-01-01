<?php

namespace Appercode\Contracts\Onboarding;

use Appercode\Contracts\Onboarding\EntityInterface;

use Illuminate\Support\Collection;

interface Task extends EntityInterface
{
    public static function confirmationTypes(): Collection;
}
