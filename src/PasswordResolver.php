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
        try {
            $response = $this->client
                ->withHeaders(['X-Aws-Parameters-Secrets-Token' => $_SERVER['AWS_SESSION_TOKEN']])
                ->get("http://localhost:2773/secretsmanager/get?secretId=$secret");
            
            $body = (string) $response->getBody();
            $result = json_decode($body, true);

            if (isset($result['SecretString'])) {
                $result = json_decode($result['SecretString'], true);
            }
        } catch (Throwable $e) {
            $this->logger->error('Failed to retrieve password from cache server. ' . $e);

            $response = $this->smClient->getSecretValue(['SecretId' => $secret]);
            $result = json_decode($response['SecretString'], true);
        }

        return $result['password'];
    }
}