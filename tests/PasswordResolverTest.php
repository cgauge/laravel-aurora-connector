<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Aurora;

use Aws\SecretsManager\SecretsManagerClient;
use CustomerGauge\Aurora\PasswordResolver;
use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PasswordResolverTest extends TestCase
{
    public function testFallbackToSecretsManagerWhenCacheServerIsDown()
    {
        $secretName = 'secretName';
        $password = 'password';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $smClient = $this->createMock(SecretsManagerClient::class);
        $smClient->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', ['SecretId' => $secretName])
            ->willReturn(['SecretString' => json_encode(['password' => $password])]);

        $sut = new PasswordResolver(new Factory(), $smClient, $logger);

        $result = $sut->resolve($secretName, false);

        $this->assertEquals($password, $result);
    }
}