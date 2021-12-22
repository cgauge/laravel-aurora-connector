<?php declare(strict_types=1);

namespace CustomerGauge\Aurora;

use Exception;
use Illuminate\Database\Connectors\MySqlConnector;
use PDOException;

final class AuroraConnector extends MySqlConnector
{
    public function __construct(private PasswordResolver $resolver)
    {}

    public function createConnection($dsn, array $config, array $options)
    {
        // If the developer explicitly set the `password` attribute on the database
        // configuration, we'll go ahead and establish a regular connection. This
        // is useful for automation tests that bypass the connection process.
        if (! empty($config['password'])) {
            return parent::createConnection($dsn, $config, $options);
        }

        $execute = function (int $attempt) use ($dsn, $config, $options) {
            if (! isset($config['aurora']['secret'])) {
                throw new Exception('The secret name must be defined on database.{connection}.aurora.secret');
            }

            // The Password Resolver extension will keep a cache of the password.
            // If Laravel throws an exception because of wrong password, then
            // we can retry but ask the extension to refresh the cache.
            $refreshCache = $attempt > 1;

            $config['password'] = $this->resolver->resolve($config['aurora']['secret'], $refreshCache);

            return parent::createConnection($dsn, $config, $options);
        };

        $condition = fn (Exception $e) => $e instanceof PDOException && str_contains($e->getMessage(), 'Access denied for user');

        return retry(when: $condition, callback: $execute, times: 3);
    }

    /**
     * Allow Aurora to use read committed from the read replica to reduce history length
     * maintained by the writer replica.
     * @see https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/AuroraMySQL.Reference.html#AuroraMySQL.Reference.IsolationLevels
     */
    protected function configureIsolationLevel($connection, array $config)
    {
        $connection->prepare('SET SESSION aurora_read_replica_read_committed = ON')->execute();

        $connection->prepare('SET SESSION TRANSACTION ISOLATION LEVEL read committed')->execute();
    }
}
