<?php

namespace Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit_Framework_TestCase;

abstract class AbstractTestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function setUp()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(true);

        Config::shouldReceive('get')
            ->with('auditing.audit_console')
            ->andReturn(true);

        parent::setUp();
    }
}
