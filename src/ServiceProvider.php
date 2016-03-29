<?php

namespace EloquentFilter;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/eloquentfilter.php' => config_path('eloquentfilter.php'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFilterGeneratorCommand();
    }

    private function registerFilterGeneratorCommand()
    {
        $this->app->singleton('command.eloquentfilter.make', function ($app) {
            return $app['EloquentFilter\Commands\MakeEloquentFilter'];
        });
        $this->commands('command.eloquentfilter.make');
    }
}
