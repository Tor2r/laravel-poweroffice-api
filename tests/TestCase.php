<?php

namespace Tor2r\PowerOfficeApi\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tor2r\PowerOfficeApi\PowerOfficeApiServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PowerOfficeApiServiceProvider::class,
        ];
    }
}
