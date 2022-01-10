<?php declare(strict_types=1);

namespace CustomerGauge\Aurora;

use Illuminate\Support\ServiceProvider;
use Aws\SecretsManager\SecretsManagerClient;

final class AwsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SecretsManagerClient::class, function () {
            $config = $this->app['config']->get('aws');

            return new SecretsManagerClient([
                'version' => '2017-10-17',
                'region' => $config['region'],
            ]);
        });
    }
}
