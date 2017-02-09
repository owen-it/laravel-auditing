<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(true);

        Config::shouldReceive('get')
            ->with('audit.console', false)
            ->andReturn(true);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }
}
