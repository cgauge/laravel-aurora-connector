<?php declare(strict_types=1);

namespace CustomerGauge\Aurora;

use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;
use Throwable;

final class PasswordResolver
{
    public function __construct(
        private Factory $client,
        private SecretsManagerClient $smClient,
        private LoggerInterface $logger
    ) {}

    public function resolve(string $secret, bool $refresh)
    {
        $refresh = (int) $refresh;

        try {
            $result = $this->client->post("http://localhost:8015/cache?name=$secret&refresh=$refresh");
        } catch (Throwable $e) {
            $this->logger->error('Failed to retrieve password from cache server. ' . $e);

            $response = $this->smClient->getSecretValue(['SecretId' => $secret]);
            $result = json_decode($response['SecretString'], true);
        }

        return $result['password'];
    }
}