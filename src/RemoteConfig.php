<?php

namespace Linx\RemoteConfigClient;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Arr;

class RemoteConfig
{
    const REQUEST_TIMEOUT = 3; // seconds

    const REQUEST_URI = '/api/v1/configs/%s/%s/%s';

    const RC_CACHE_FALLBACK = 'RC_CACHE_FALLBACK';
    const RC_CACHE_FALLBACK_TTL = 604800; //one week
    const CACHE_TTL = -1;

    private $host;

    private $username;

    private $password;

    private $application;

    private $environment;

    private $cacheLifeTime;

    private $httpClient;

    private $cache;

    private $cacheFallback;

    public function __construct(array $credentials)
    {
        $this->host = $this->addScheme($credentials['host']);
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
        $this->application = $credentials['application'];
        $this->environment = $credentials['environment'];
        $this->cacheLifeTime = $credentials['cache-life-time'] ?? self::CACHE_TTL;
        $this->cacheDirectory = $credentials['cache-directory'] ?? null;
        $this->cacheFallbackDirectory = $credentials['cache-fallback-directory'] ?? null;
    }

    public function getClientConfig(string $client, string $config = null)
    {
        $uri = $this->buildUri($this->application, $client, $this->environment);
        $cacheKey = $this->buildCacheKey($uri);
        $cache = $this->getCache();
        $hasCache = false;
        $canAcessRedis = false;

        try {
            if (method_exists($cache, 'tags')) {
                $cache = $cache->tags($this->getCacheTags($client));
            }
            $hasCache = $cache->has($cacheKey);
            $canAcessRedis = true;
        } catch (\Exception $th) {}

        if ($hasCache) {
            $data = $cache->get($cacheKey);

            if(!$this->cacheFallback()->has($cacheKey)) {
                $this->cacheFallback()->set($cacheKey, $data, self::RC_CACHE_FALLBACK_TTL);
            }
        } else {
            $data = $this->httpGet($uri);
            if($canAcessRedis) {
                $cache->set($cacheKey, $data, $this->cacheLifeTime);
            }
        }

        return Arr::get($data, $config, null);
    }

    private function getCacheTags($client)
    {
        return [$client, "{$client}-remoteconfig"];
    }

    private function httpGet($path)
    {
        $cache = $this->cacheFallback();
        $cacheKey = $this->buildCacheKey($path);
        $timeout = self::REQUEST_TIMEOUT;

        $currentCache = $cache->get($cacheKey);

        try {
            $response = $this->getHttpClient()->request(
                'GET',
                $this->host . $path,
                [
                    'auth' => [$this->username, $this->password],
                    'connect_timeout' => $timeout,
                    'read_timeout' => $timeout,
                    'timeout' => $timeout,
                ]
            );
            $cache->set($cacheKey, json_decode($response->getBody(), true), self::RC_CACHE_FALLBACK_TTL);
        } catch (ConnectException $e) {
            $cache->set($cacheKey, $currentCache, self::RC_CACHE_FALLBACK_TTL);
        }

        $data = $cache->get($cacheKey);

        if(!$data) {
            throw new \Exception("Connection error on: '{$this->host}{$path}'");
        }

        return $data;
    }

    private function cacheFallback()
    {
        if (!empty($this->cacheFallback)) {
            return $this->cacheFallback;
        }

        return $this->cacheFallback = new FilesystemCache(
            self::RC_CACHE_FALLBACK,
            self::RC_CACHE_FALLBACK_TTL,
            $this->cacheFallbackDirectory
        );
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

    public function updateCacheData(string $client, array $data, bool $canExpire = true)
    {
        $uri = $this->buildUri($this->application, $client, $this->environment);
        $cacheKey = $this->buildCacheKey($uri);

        $cache = $this->getCache();
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags($this->getCacheTags($client));
        }

        $cacheLifeTime = $canExpire ? $this->cacheLifeTime : null;

        $cache->set($cacheKey, $data, $cacheLifeTime);
    }

    private function addScheme($url, $scheme = 'http://')
    {
        return parse_url($url, PHP_URL_SCHEME) === null
            ? $scheme . $url
            : $url;
    }

    private function buildUri(
        string $application,
        string $client,
        string $environment
    ): string {
        return sprintf(
            self::REQUEST_URI,
            $application,
            $client,
            $environment
        );
    }

    private function buildCacheKey(string $uri): string
    {
        return md5($uri);
    }
}
