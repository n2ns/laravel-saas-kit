<?php

namespace N2ns\SaasKit;

use N2ns\SaasKit\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class SaasKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
