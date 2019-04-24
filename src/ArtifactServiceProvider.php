<?php

namespace FRohlfing\Artifact;

use FRohlfing\Artifact\Console\Commands\ArtifactCreateCommand;
use Illuminate\Support\ServiceProvider;

class ArtifactServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * Wenn das Package Routen beinhaltet, muss hier false stehen!
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered, meaning you have access to all
     * other services that have been registered by the framework.
     *
     * @return void
     */
    public function boot()
    {
        // commands
        if ($this->app->runningInConsole()) {
            $this->commands([ArtifactCreateCommand::class]);
            //$this->commands([ArtifactRemoveCommand::class]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
