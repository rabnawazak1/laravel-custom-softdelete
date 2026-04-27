<?php

namespace Rabnawazak1\CustomSoftDelete\Providers;

use Illuminate\Support\ServiceProvider;
use Rabnawazak1\CustomSoftDelete\Commands\AddSoftDeleteColumns;

class CustomSoftDeleteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([AddSoftDeleteColumns::class]);
        }
    }
}