<?php

namespace Linx\RemoteConfigClient;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\Exception\ConnectException;
use Exception;

class RemoteConfig
{
    const REQUEST_TIMEOUT = 3; // seconds

    private $host;

    private $username;

    private $password;

    private $application;

    private $environment;

    private $cacheLifeTime;

    private $httpClient;

    private $cache;

    public function __construct(array $credentials)
    {
        $this->host = $this->addScheme($credentials['host']);
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
        $this->application = $credentials['application'];
        $this->environment = $credentials['environment'];
        $this->cacheLifeTime = isset($credentials['cache-life-time']) ? $credentials['cache-life-time'] : 3600;
        $this->cacheDirectory = isset($credentials['cache-directory']) ? $credentials['cache-directory'] : null;
    }

    public function getClientConfig(string $client, string $config = null)
    {
        $uri = "/api/v1/configs/{$this->application}/{$client}/{$this->environment}";
        $cacheKey = md5($uri);

        $cache = $this->getCache();
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags($this->getCacheTags($client));
        }

        if ($cache->has($cacheKey)) {
            $data = $cache->get($cacheKey);
        } else {
            try {
                $data = $this->httpGet($uri);
            } catch (ConnectException $e) {
                 throw new \Exception("Connection error on: '{$this->host}{$uri}'. \n {$e->getMessage()}");
            }
            $cache->set($cacheKey, $data, $this->cacheLifeTime);
        }

        return array_get($data, $config, null);
    }

    private function getCacheTags($client)
    {
        return [ $client, "{$client}-remoteconfig" ];
    }

    private function httpGet($path)
    {
        $response = $this->getHttpClient()->request(
            'GET',
            $this->host . $path, [
                'auth' => [ $this->username, $this->password ],
                'connect_timeout' => self::REQUEST_TIMEOUT,
                'read_timeout' => self::REQUEST_TIMEOUT,
                'timeout' => self::REQUEST_TIMEOUT,
            ]
        );

        return json_decode($response->getBody(), true);
    }

    public function getHttpClient()
    {
        if (!empty($this->httpClient)) {
            return $this->httpClient;
        }

        $httpClient = new Client();

        return $this->httpClient = $httpClient;
    }

    public function getCache()
    {
        if (!empty($this->cache)) {
            return $this->cache;
        }

        $cache = new FilesystemCache('', $this->cacheLifeTime, $this->cacheDirectory);

        return $this->cache = $cache;
    }

    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    private function addScheme($url, $scheme = 'http://')
    {
        return parse_url($url, PHP_URL_SCHEME) === null
        ? $scheme . $url
        : $url;
    }
}
