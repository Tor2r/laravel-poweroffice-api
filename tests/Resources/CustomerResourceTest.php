<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\CustomerResource;

beforeEach(function () {
    Cache::flush();

    $this->client = new PowerOfficeClient(
        appKey: 'test-app-key',
        clientKey: 'test-client-key',
        subscriptionKey: 'test-sub-key',
        baseUrl: 'https://goapi-demo.poweroffice.net/v2',
        tokenUrl: 'https://goapi-demo.poweroffice.net/OAuth/Token',
    );

    $this->resource = new CustomerResource($this->client);
});

it('gets a customer by id', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Customers/42' => Http::response(['id' => 42, 'name' => 'Acme']),
    ]);

    $result = $this->resource->get(42);

    expect($result)->toBe(['id' => 42, 'name' => 'Acme']);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/Customers/42'));
});

it('lists customers with filters', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Customers*' => Http::response([['id' => 1], ['id' => 2]]),
    ]);

    $result = $this->resource->list(['status' => 'active']);

    expect($result)->toHaveCount(2);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/Customers')
        && str_contains($request->url(), 'status=active'));
});

it('creates a customer', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Customers' => Http::response(['id' => 99, 'name' => 'New Co'], 201),
    ]);

    $result = $this->resource->create(['name' => 'New Co']);

    expect($result)->toMatchArray(['id' => 99, 'name' => 'New Co']);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/Customers')
        && $request['name'] === 'New Co');
});

it('updates a customer', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Customers/42' => Http::response(['id' => 42, 'name' => 'Updated']),
    ]);

    $result = $this->resource->update(42, ['name' => 'Updated']);

    expect($result)->toMatchArray(['id' => 42, 'name' => 'Updated']);

    Http::assertSent(fn ($request) => $request->method() === 'PUT'
        && str_contains($request->url(), '/Customers/42')
        && $request['name'] === 'Updated');
});
