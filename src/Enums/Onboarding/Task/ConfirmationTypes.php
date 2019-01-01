<?php

namespace Appercode\Enums\Onboarding\Task;

use Illuminate\Support\Collection;

class ConfirmationTypes
{
    const CONFIRMATION_TYPE_BY_PERFORMER = 'byPerformer';
    const CONFIRMATION_TYPE_BY_MENTOR = 'byMentor';
    const CONFIRMATION_TYPE_BY_ADMINISTRATOR = 'byAdministrator';
    const CONFIRMATION_TYPE_BY_FORM = 'byForm';

    public static function list(): Collection
    {
        return new Collection([
            self::CONFIRMATION_TYPE_BY_PERFORMER,
            self::CONFIRMATION_TYPE_BY_MENTOR,
            self::CONFIRMATION_TYPE_BY_ADMINISTRATOR,
            self::CONFIRMATION_TYPE_BY_FORM
        ]);
    }
}
