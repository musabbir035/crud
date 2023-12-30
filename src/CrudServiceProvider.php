<?php

namespace Musabbir035\Crud;

use Illuminate\Support\ServiceProvider;
use Musabbir035\Crud\Console\CrudCommand;

class CrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudCommand::class,
            ]);
        }

        // $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'crudify');
        // $this->publishes([__DIR__ . '/../../resources/views' => resource_path('views/vendor/crudify')], 'views');
    }
}
