<?php

namespace Tests;

use Dotenv\Dotenv;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $dotenv = new Dotenv(__DIR__ . '/..');
        $dotenv->load();

        $app['config']->set('appercode.server', getenv('APPERCODE_SERVER'));
        $app['config']->set('appercode.project', getenv('APPERCODE_DEFAULT_BACKEND'));
    }
}
