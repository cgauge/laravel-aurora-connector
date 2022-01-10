# Laravel Aurora Connector 📮

This library creates a new database driver for Laravel dedicated to connect to Aurora. It gives us an opportunity to properly configure the READ COMMITTED isolation as well as
load the database password from AWS Secrets.

# Installation

```bash
composer require customergauge/aurora
```

# Usage

In the `database.php`, we define a connection using the `aurora` driver

```php
        'tenant' => [
            'driver' => 'aurora',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => [],
            'aurora' => [
                'secret' => env('AWS_SECRET_NAME'),
            ],
        ],
```

The new 'aurora' key will allow the confiugration for the AWS Secret manager.

### Isolation Levels 
By default we allow Aurora to use read committed from the read replica to reduce history length maintained by the writer replica.
If you want to turn it off, go in the `database.php`, inside the connection that you want and add

```php
        'tenant' => [
            'driver' => 'aurora',
            'isolation_level' => 'off'
            ...
        ],
```

Click to know more about [Isolations Levels](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/AuroraMySQL.Reference.html#AuroraMySQL.Reference.IsolationLevels)


### Extension
@TODO
