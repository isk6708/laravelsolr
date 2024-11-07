<?php

namespace HaiderJabbar\LaravelSolr;

use Illuminate\Support\ServiceProvider;

class SolrServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publishing configuration
        $this->publishes([
            __DIR__.'/../config/solr.php' => config_path('solr.php'),
        ], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merging package config with app config
        $this->mergeConfigFrom(__DIR__.'/../config/solr.php', 'solr');

        // Binding the Solr class
        $this->app->singleton('solr', function () {
            return new Solr();
        });
    }
}
