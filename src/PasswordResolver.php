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

    private function retrieveFromCacheServer(string $secret)
    {
        $response = $this->client
            ->withHeaders(['X-Aws-Parameters-Secrets-Token' => $_SERVER['AWS_SESSION_TOKEN']])
            ->get("http://localhost:2773/secretsmanager/get?secretId=$secret");

        $result = json_decode($response->json('SecretString'), true);

        return $result['password'];
    }

    private function retrieveFromSecretManager(string $secret)
    {
        $response = $this->smClient->getSecretValue(['SecretId' => $secret]);

        $result = json_decode($response['SecretString'], true);

        return $result['password'];
    }

    public function resolve(string $secret, bool $fresh)
    {
        if ($fresh) {
            return $this->retrieveFromSecretManager($secret);
        }

        try {
            return $this->retrieveFromCacheServer($secret);
        } catch (Throwable $e) {
            $this->logger->error('Failed to retrieve password from cache server. ' . $e);

            return $this->retrieveFromSecretManager($secret);
        }
    }
}