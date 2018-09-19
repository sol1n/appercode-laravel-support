<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Settings;

use Carbon\Carbon;

class GetCurrentDateTimeTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
    }

    public function test_can_revice_current_time()
    {
        $time = Settings::time($this->user->backend);
        $this->assertEquals($time instanceof Carbon, true);
    }
}
