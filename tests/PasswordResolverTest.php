<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Aurora;

use Aws\SecretsManager\SecretsManagerClient;
use CustomerGauge\Aurora\PasswordResolver;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;

class PasswordResolverTest extends TestCase
{
    public function testRetrieveSecretFromCacheServer()
    {
        $secretName = 'secretName';
        $password = 'password';

        $logger = $this->createMock(LoggerInterface::class);
        $smClient = $this->createMock(SecretsManagerClient::class);
        $factory = $this->createMock(Factory::class);

        $body = json_encode(['SecretString' => json_encode(['password' => $password])]);
        $headers['Content-Type'] = 'application/json';

        $factory->expects($this->exactly(2))
            ->method('__call')
            ->willReturn(
                $factory,
                new Response(new Psr7Response(200, $headers, $body))
            );
            
        $sut = new PasswordResolver($factory, $smClient, $logger);

        $result = $sut->resolve($secretName, false);

        $this->assertEquals($password, $result);
    }

    public function testFallbackToSecretsManagerWhenCacheServerIsDown()
    {
        $secretName = 'secretName';
        $password = 'password';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $smClient = $this->createMock(SecretsManagerClient::class);
        $smClient->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => $secretName]])
            ->willReturn(['SecretString' => json_encode(['password' => $password])]);

        $sut = new PasswordResolver(new Factory(), $smClient, $logger);

        $result = $sut->resolve($secretName, false);

        $this->assertEquals($password, $result);
    }

    public function testItDoesNotCallCacheServerWhenFreshIsTrue()
    {
        $secretName = 'secretName';
        $password = 'password';

        $logger = $this->createMock(LoggerInterface::class);
        $smClient = $this->createMock(SecretsManagerClient::class);

        $smClient->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => $secretName]])
            ->willReturn(['SecretString' => json_encode(['password' => $password])]);

        $sut = new PasswordResolver(new Factory(), $smClient, $logger);

        $result = $sut->resolve($secretName, true);

        $this->assertEquals($password, $result);
    }
}