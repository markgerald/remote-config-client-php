<?php

use Linx\RemoteConfigClient\RemoteConfig;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\FilesystemCache;

class FormBuilderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testGetClientConfig()
    {
        $remoteConfig = new RemoteConfig([
            'host' => 'http://remote-config',
            'username' => 'username@email.com',
            'password' => 'password',
            'application' => 'application',
            'environment' => 'environment',
        ]);

        $cacheMock = Mockery::mock(FilesystemCache::class);
        $httpClientMock = Mockery::mock(Client::class);

        $remoteConfig->setHttpClient($httpClientMock);
        $remoteConfig->setCache($cacheMock);

        $cacheMock
            ->shouldReceive('has')
            ->with(md5('/api/v1/configs/application/client/environment'))
            ->once()
            ->andReturn(false);

        $httpClientMock
            ->shouldReceive('request')
            ->with('GET', 'http://remote-config/api/v1/configs/application/client/environment', [
                'auth' => ['username@email.com', 'password'],
                'connect_timeout' => RemoteConfig::REQUEST_TIMEOUT,
                'read_timeout' => RemoteConfig::REQUEST_TIMEOUT,
                'timeout' => RemoteConfig::REQUEST_TIMEOUT,
                ])
            ->once()
            ->andReturn(Mockery::self())
            ->getMock()
            ->shouldReceive('getBody')
            ->once()
            ->andReturn('{}');

        $cacheMock
            ->shouldReceive('set')
            ->with(md5('/api/v1/configs/application/client/environment'), [], 3600)
            ->once()
            ->andReturn(true);

        $remoteConfig->getClientConfig('client');
    }
}
