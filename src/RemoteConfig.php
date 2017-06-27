<?php

namespace Linx\RemoteConfigClient;

use GuzzleHttp\Client;


class RemoteConfig
{
    private $host;

    private $username;

    private $password;

    private $application;

    private $environment;

    public function __construct(array $credentials)
    {
        $this->host = $credentials['host'];
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
        $this->application = $credentials['application'];
        $this->environment = $credentials['environment'];
    }

    public function getClientConfig(string $client, string $config = null)
    {


        $guzzleClient = new Client([
            'base_uri' => $this->host
        ]);

        $uri = "api/v1/configs/{$this->application}/{$client}/{$this->environment}";

        $response = $guzzleClient->request('GET', $uri, ['auth' => [$this->username, $this->password]]);

        $data = json_decode($response->getBody(), true);

        return array_get($data, $config, null);
    }
}
