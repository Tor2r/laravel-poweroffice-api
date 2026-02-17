<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\ProjectResource;

beforeEach(function () {
    Cache::flush();

    $this->client = new PowerOfficeClient(
        appKey: 'test-app-key',
        clientKey: 'test-client-key',
        subscriptionKey: 'test-sub-key',
        baseUrl: 'https://goapi-demo.poweroffice.net/v2',
        tokenUrl: 'https://goapi-demo.poweroffice.net/OAuth/Token',
    );

    $this->resource = new ProjectResource($this->client);
});

it('gets a project by id', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Projects/300' => Http::response(['id' => 300, 'name' => 'Website Redesign']),
    ]);

    $result = $this->resource->get(300);

    expect($result)->toBe(['id' => 300, 'name' => 'Website Redesign']);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/Projects/300'));
});

it('lists projects with filters', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Projects*' => Http::response([['id' => 1], ['id' => 2]]),
    ]);

    $result = $this->resource->list(['status' => 'Active']);

    expect($result)->toHaveCount(2);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_contains($request->url(), '/Projects')
        && str_contains($request->url(), 'status=Active'));
});

it('creates a project', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Projects' => Http::response(['id' => 300, 'name' => 'New Project'], 201),
    ]);

    $result = $this->resource->create(['name' => 'New Project']);

    expect($result)->toMatchArray(['id' => 300, 'name' => 'New Project']);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/Projects')
        && $request['name'] === 'New Project');
});

it('updates a project with patch operations', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
        '*/v2/Projects/300' => Http::response(['id' => 300, 'name' => 'Updated Project']),
    ]);

    $operations = [
        ['op' => 'replace', 'path' => '/Name', 'value' => 'Updated Project'],
    ];

    $result = $this->resource->update(300, $operations);

    expect($result)->toMatchArray(['id' => 300, 'name' => 'Updated Project']);

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && str_contains($request->url(), '/Projects/300'));
});
