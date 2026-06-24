<?php

namespace N2ns\SaasKit\Tests;

use N2ns\SaasKit\SaasKitServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SaasKitServiceProvider::class,
        ];
    }
}
