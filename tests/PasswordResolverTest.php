<?php

namespace Tests\CustomerGauge\Aurora;

use Aws\SecretsManager\SecretsManagerClient;
use CustomerGauge\Aurora\PasswordResolver;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;

class PasswordResolverTest extends TestCase
{
    public function testFallbackToSecretsManagerWhenCacheServerIsDown()
    {
        $secretName = 'secretName';
        $password = 'password';

        $smClient = $this->createMock(SecretsManagerClient::class);
        $smClient->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', [$secretName])
            ->willReturn(['SecretString' => json_encode(['password' => $password])]);

        $sut = new PasswordResolver(new Factory(), $smClient);

        $result = $sut->resolve($secretName, false);

        $this->assertEquals($password, $result);
    }
}