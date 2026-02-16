<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeApiException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeAuthException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeValidationException;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\CustomerResource;
use Tor2r\PowerOfficeApi\Resources\ProductResource;
use Tor2r\PowerOfficeApi\Resources\SalesOrderResource;

beforeEach(function () {
    Cache::flush();

    $this->client = new PowerOfficeClient(
        appKey: 'test-app-key',
        clientKey: 'test-client-key',
        subscriptionKey: 'test-sub-key',
        baseUrl: 'https://goapi-demo.poweroffice.net/v2',
        tokenUrl: 'https://goapi-demo.poweroffice.net/OAuth/Token',
        tokenTtl: 900,
    );
});

it('authenticates with correct credentials', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'test-token']),
    ]);

    $token = $this->client->authenticate();

    expect($token)->toBe('test-token');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://goapi-demo.poweroffice.net/OAuth/Token'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-app-key:test-client-key'))
            && $request->hasHeader('Ocp-Apim-Subscription-Key', 'test-sub-key')
            && $request['grant_type'] === 'client_credentials';
    });
});

it('caches the access token', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'cached-token']),
    ]);

    $token1 = $this->client->getAccessToken();
    $token2 = $this->client->getAccessToken();

    expect($token1)->toBe('cached-token');
    expect($token2)->toBe('cached-token');

    Http::assertSentCount(1);
});

it('throws auth exception on failed authentication', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    $this->client->authenticate();
})->throws(PowerOfficeAuthException::class, 'Failed to authenticate with PowerOffice API.');

it('throws auth exception when no access token is returned', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['something_else' => 'value']),
    ]);

    $this->client->authenticate();
})->throws(PowerOfficeAuthException::class, 'PowerOffice API did not return an access token.');

it('sends bearer token and subscription key on api requests', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'my-bearer']),
        '*/v2/*' => Http::response(['data' => 'value']),
    ]);

    $this->client->get('/Customers');

    Http::assertSent(function ($request) {
        if (str_contains($request->url(), '/Customers')) {
            return $request->hasHeader('Authorization', 'Bearer my-bearer')
                && $request->hasHeader('Ocp-Apim-Subscription-Key', 'test-sub-key');
        }

        return true;
    });
});

it('flushes the cached token', function () {
    Http::fake([
        '*/OAuth/Token' => Http::sequence()
            ->push(['access_token' => 'first-token'])
            ->push(['access_token' => 'second-token']),
    ]);

    $first = $this->client->getAccessToken();
    $this->client->flushToken();
    $second = $this->client->getAccessToken();

    expect($first)->toBe('first-token');
    expect($second)->toBe('second-token');
});

it('throws validation exception on 422 response', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['errors' => ['name' => ['required']]], 422),
    ]);

    $this->client->post('/Customers', ['name' => '']);
})->throws(PowerOfficeValidationException::class, 'Validation error from PowerOffice API.');

it('throws api exception on other error responses', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'not found'], 404),
    ]);

    $this->client->get('/Customers/999');
})->throws(PowerOfficeApiException::class);

it('returns customers resource', function () {
    expect($this->client->customers())->toBeInstanceOf(CustomerResource::class);
});

it('returns products resource', function () {
    expect($this->client->products())->toBeInstanceOf(ProductResource::class);
});

it('returns sales orders resource', function () {
    expect($this->client->salesOrders())->toBeInstanceOf(SalesOrderResource::class);
});
