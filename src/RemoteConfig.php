<?php

namespace Flaviozantut\RemoteConfig;

use GuzzleHttp\Client;

class RemoteConfig
{
    private $domain;

    private $authToken;

    private $client;

    public function __construct($domain, $authToken)
    {
        $this->domain = $domain;
        $this->authToken = $authToken;
    }

    public function get($value = '')
    {
        $client = new Client([
            'base_uri' => $this->domain,
            'timeout'  => 2.0,
        ]);

        $response = $client->request('GET', 'v1/config?auth_token=' . $this->authToken);

        return json_decode($response->getBody(), true);
    }
}
