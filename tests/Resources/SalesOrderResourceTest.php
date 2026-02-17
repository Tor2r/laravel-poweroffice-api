<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\SalesOrderResource;

beforeEach(function () {
    Cache::flush();

    $this->client = new PowerOfficeClient(
        appKey: 'test-app-key',
        clientKey: 'test-client-key',
        subscriptionKey: 'test-sub-key',
        baseUrl: 'https://goapi-demo.poweroffice.net/v2',
        tokenUrl: 'https://goapi-demo.poweroffice.net/OAuth/Token',
    );

    $this->resource = new SalesOrderResource($this->client);
});

it('gets a sales order by id', function () {
    $uuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        "*/v2/SalesOrders/{$uuid}" => Http::response(['id' => $uuid, 'total' => 1500]),
    ]);

    $result = $this->resource->get($uuid);

    expect($result)->toBe(['id' => $uuid, 'total' => 1500]);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), "/SalesOrders/{$uuid}"));
});

it('lists sales orders with filters', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/SalesOrders*' => Http::response([['id' => 1], ['id' => 2], ['id' => 3]]),
    ]);

    $result = $this->resource->list(['customerId' => 42]);

    expect($result)->toHaveCount(3);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/SalesOrders')
        && str_contains($request->url(), 'customerId=42'));
});

it('creates a sales order', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/SalesOrders' => Http::response(['id' => 100, 'customerId' => 42], 201),
    ]);

    $result = $this->resource->create(['customerId' => 42, 'lines' => []]);

    expect($result)->toMatchArray(['id' => 100, 'customerId' => 42]);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/SalesOrders')
        && $request['customerId'] === 42);
});
