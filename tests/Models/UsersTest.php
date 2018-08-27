<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;

class UsersTest extends TestCase
{
    /**
     * @group auth
     */
    public function test_user_can_login_by_password()
    {
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));

        $this->assertNotNull($user->id);
        $this->assertNotNull($user->token);
        $this->assertNotNull($user->refreshToken);
    }

    /**
     * @group auth
     */
    public function test_user_can_login_by_token()
    {
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));

        $newUser = User::loginByToken((new Backend), $user->refreshToken);

        $this->assertNotNull($newUser->token);
        $this->assertEquals($user->id, $newUser->id);
    }

    /**
     * @group auth
     */
    public function test_user_can_get_via_static_current_method()
    {
        $user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));

        $currentUser = User::current();

        $this->assertEquals($user->id, $currentUser->id);
        $this->assertEquals($user->token, $currentUser->token);
        $this->assertEquals($user->refreshToken, $currentUser->refreshToken);
    }
}
