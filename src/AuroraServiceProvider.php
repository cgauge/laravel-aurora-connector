<?php declare(strict_types=1);

namespace CustomerGauge\Aurora;

use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;

final class AuroraServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->defaultKeyLength();

        $this->registerAuroraDriver();
    }

    private function registerAuroraDriver(): void
    {
        // We register an `aurora` driver which extends the MySQL drivers from Laravel.
        // The only thing we want to change is how the Transaction Isolation Level
        // are handled. We need to enable `aurora_read_replica_read_committed`
        // before we can enable read committed and skip the history length.
        // see: https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/AuroraMySQL.Reference.html#AuroraMySQL.Reference.IsolationLevels
        $factory = function ($connection, $database, $prefix, $config) {
            return new MySqlConnection($connection, $database, $prefix, $config);
        };

        Connection::resolverFor('aurora', $factory);

        $this->app->bind('db.connector.aurora', AuroraConnector::class);
    }

    private function defaultKeyLength(): void
    {
        /**
         * Mysql 5.6 uses a smaller key than Mysql 5.7
         * read more on: https://laravel-news.com/laravel-5-4-key-too-long-error
         * UPDATE: Instead of using Schema::defaultStringLength, we use Builder directly.
         * This is a static method, but if we use Schema, then the Laravel Container
         * will instantiate and cache all of the database config inside the Database Manager.
         * We don't want Laravel to do that because we may change the database configuration
         * on-the-fly during PHPUnit tests.
         * By going directly at the underlying class, we achieve the same result without
         * going through Laravel Facade and causing the config info to be cached.
         */
        Builder::defaultStringLength(191);
    }
}