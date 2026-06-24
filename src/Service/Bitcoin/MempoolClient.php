<?php

declare(strict_types=1);

namespace App\Service\Bitcoin;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MempoolClient
{
    private const BASE_URL = 'https://mempool.space/api';
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getAddressInfo(string $address): array
    {
        $cacheKey = 'mempool_address_'.$address;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($address): array {
            $item->expiresAfter(self::CACHE_TTL);

            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL.'/address/'.$address
            );

            return $response->toArray();
        });
    }

    public function getAddressTransactions(string $address): array
    {
        $cacheKey = 'mempool_txs_'.$address;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($address): array {
            $item->expiresAfter(self::CACHE_TTL);

            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL.'/address/'.$address.'/txs'
            );

            return $response->toArray();
        });
    }
}
