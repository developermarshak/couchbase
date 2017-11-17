<?php namespace Mpociot\Couchbase;

use Illuminate\Support\ServiceProvider;
use \Illuminate\Database\Connection;

class CouchbaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        $this->app->singleton('couchbase.connection', function($app){
            $connectionName = config('database.connections.'.config('database.default'));
            return new Connection($connectionName);
        });

        Connection::resolverFor('couchbase', function ($config) {
            return app('couchbase.connection');
        });

        $this->app->resolving('db', function ($db) {
            $db->extend('couchbase', function ($config) {
                return app('couchbase.connection');
            });
        });
    }
}
