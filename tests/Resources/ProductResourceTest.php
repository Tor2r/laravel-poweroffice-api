<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\ProductResource;

beforeEach(function () {
    Cache::flush();

    $this->client = new PowerOfficeClient(
        appKey: 'test-app-key',
        clientKey: 'test-client-key',
        subscriptionKey: 'test-sub-key',
        baseUrl: 'https://goapi-demo.poweroffice.net/v2',
        tokenUrl: 'https://goapi-demo.poweroffice.net/OAuth/Token',
    );

    $this->resource = new ProductResource($this->client);
});

it('gets a product by id', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Products/10' => Http::response(['id' => 10, 'name' => 'Widget']),
    ]);

    $result = $this->resource->get(10);

    expect($result)->toBe(['id' => 10, 'name' => 'Widget']);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/Products/10'));
});

it('lists products with filters', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Products*' => Http::response([['id' => 1], ['id' => 2]]),
    ]);

    $result = $this->resource->list(['category' => 'tools']);

    expect($result)->toHaveCount(2);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/Products')
        && str_contains($request->url(), 'category=tools'));
});

it('creates a product', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Products' => Http::response(['id' => 50, 'name' => 'Gadget'], 201),
    ]);

    $result = $this->resource->create(['name' => 'Gadget']);

    expect($result)->toMatchArray(['id' => 50, 'name' => 'Gadget']);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/Products')
        && $request['name'] === 'Gadget');
});

it('updates a product', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Products/10' => Http::response(['id' => 10, 'name' => 'Updated Widget']),
    ]);

    $result = $this->resource->update(10, ['name' => 'Updated Widget']);

    expect($result)->toMatchArray(['id' => 10, 'name' => 'Updated Widget']);

    Http::assertSent(fn ($request) => $request->method() === 'PUT'
        && str_contains($request->url(), '/Products/10')
        && $request['name'] === 'Updated Widget');
});
