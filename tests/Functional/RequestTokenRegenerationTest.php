<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;

class RequestTokenRegenerationTest extends TestCase
{
    public function test_can_revice_data_with_irrelevant_token()
    {
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));

        $elements = Element::list('Favorites', $user->backend, ['take' => 1]);

        $this->assertEquals($elements->count(), 1);

        $user->token = 'wrong token';

        $newElements = Element::list('Favorites', $user->backend, ['take' => 1]);

        $this->assertEquals($newElements->count(), 1);
    }
}
