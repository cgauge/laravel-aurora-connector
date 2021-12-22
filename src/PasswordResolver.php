<?php declare(strict_types=1);

namespace Customergauge\Aurora;

use Illuminate\Http\Client\Factory;

final class PasswordResolver
{
    public function __construct(private Factory $client) {}

    public function resolve(string $secret, bool $refresh)
    {
        $refresh = (int) $refresh;

        $result = $this->client->post("http://localhost:8015/cache?name=$secret&refresh=$refresh");

        return $result['password'];
    }
}