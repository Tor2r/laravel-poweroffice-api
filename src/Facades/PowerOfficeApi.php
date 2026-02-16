<?php

namespace Tor2r\PowerOfficeApi\Facades;

use Illuminate\Support\Facades\Facade;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\CustomerResource;
use Tor2r\PowerOfficeApi\Resources\ProductResource;
use Tor2r\PowerOfficeApi\Resources\SalesOrderResource;

/**
 * @method static string authenticate()
 * @method static string getAccessToken()
 * @method static void flushToken()
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static CustomerResource customers()
 * @method static ProductResource products()
 * @method static SalesOrderResource salesOrders()
 *
 * @see PowerOfficeClient
 */
class PowerOfficeApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PowerOfficeClient::class;
    }
}
