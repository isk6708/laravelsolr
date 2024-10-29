<?php
namespace haiderjabbar\laravelsolr;

use haiderjabbar\laravelsolr\Console\Commands\CreateSolrFields;
use haiderjabbar\laravelsolr\Console\Commands\CreateSolrCore;
use haiderjabbar\laravelsolr\Console\Commands\DeleteSolrCore;
use haiderjabbar\laravelsolr\Console\Commands\DeleteSolrFields;
use haiderjabbar\laravelsolr\Console\Commands\UpdateSolrCore;
use haiderjabbar\laravelsolr\Console\Commands\UpdateSolrFields;
use Illuminate\Support\ServiceProvider;

class LaravelSolrServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register laravelsolr services
        $this->app->singleton(laravelsolr::class, function ($app) {
            return new laravelsolr();
        });

        // Merge the configuration file
        $this->mergeConfigFrom(__DIR__.'/config/solr.php', 'solr');
    }

    public function boot()
    {
        // Publish configuration file
        // Publishing the config file to the application's config directory
        $this->publishes([
            __DIR__ . '/config/solr.php' => config_path('solr.php'),
        ]);

        // Merging the configuration so it's accessible via config('solr')
        $this->mergeConfigFrom(
            __DIR__ . '/config/solr.php', 'solr'
        );
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSolrCore::class,
                UpdateSolrCore::class,
                DeleteSolrCore::class,
                CreateSolrFields::class,
                UpdateSolrFields::class,
                DeleteSolrFields::class,
            ]);
        }
    }
}
