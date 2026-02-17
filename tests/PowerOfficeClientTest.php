<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeApiException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeAuthException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeConflictException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeNotFoundException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeValidationException;
use Tor2r\PowerOfficeApi\PowerOfficeClient;
use Tor2r\PowerOfficeApi\Resources\CustomerResource;
use Tor2r\PowerOfficeApi\Resources\ProductResource;
use Tor2r\PowerOfficeApi\Resources\ProjectResource;
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

it('returns response body on 200', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['id' => 1, 'name' => 'Test']),
    ]);

    $result = $this->client->get('/Customers/1');

    expect($result)->toBe(['id' => 1, 'name' => 'Test']);
});

it('returns response body on 201', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['id' => 99, 'name' => 'New'], 201),
    ]);

    $result = $this->client->post('/Customers', ['name' => 'New']);

    expect($result)->toBe(['id' => 99, 'name' => 'New']);
});

it('returns empty array on 204 no content', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(null, 204),
    ]);

    $result = $this->client->get('/Customers');

    expect($result)->toBe([]);
});

it('throws validation exception on 400 response', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['errors' => ['field' => ['invalid']]], 400),
    ]);

    $this->client->post('/Customers', ['field' => 'bad']);
})->throws(PowerOfficeValidationException::class, 'Bad request to PowerOffice API.');

it('throws auth exception on 401 response after retries exhausted', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    $this->client->get('/Customers');
})->throws(PowerOfficeAuthException::class, 'Unauthorized: Access token is missing or invalid.');

it('throws auth exception on 403 response', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'forbidden'], 403),
    ]);

    $this->client->get('/Customers');
})->throws(PowerOfficeAuthException::class, 'Forbidden: Integration does not have required permission to use this endpoint.');

it('throws not found exception on 404 response', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'not found'], 404),
    ]);

    $this->client->get('/Customers/999');
})->throws(PowerOfficeNotFoundException::class, 'Resource not found in PowerOffice API.');

it('throws conflict exception on 409 response', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'conflict'], 409),
    ]);

    $this->client->get('/Customers/1');
})->throws(PowerOfficeConflictException::class, 'Resource is in use and cannot be deleted.');

it('throws validation exception on 422 response', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['errors' => ['name' => ['required']]], 422),
    ]);

    $this->client->post('/Customers', ['name' => '']);
})->throws(PowerOfficeValidationException::class, 'Validation error from PowerOffice API.');

it('throws api exception on 429 response after retries exhausted', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'too many requests'], 429),
    ]);

    $this->client->get('/Customers');
})->throws(PowerOfficeApiException::class, 'PowerOffice API rate limit exceeded.');

it('throws api exception on 500 server error', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['error' => 'internal server error'], 500),
    ]);

    $this->client->get('/Customers');
})->throws(PowerOfficeApiException::class, 'PowerOffice API request failed with status 500.');

it('not found exception is an instance of api exception', function () {
    expect(new PowerOfficeNotFoundException(
        'test',
        response: Http::fake(['*' => Http::response()])->get('/'),
    ))->toBeInstanceOf(PowerOfficeApiException::class);
});

it('conflict exception is an instance of api exception', function () {
    expect(new PowerOfficeConflictException(
        'test',
        response: Http::fake(['*' => Http::response()])->get('/'),
    ))->toBeInstanceOf(PowerOfficeApiException::class);
});

it('sends a patch request', function () {
    Http::fake([
        '*/OAuth/Token' => Http::response(['access_token' => 'token']),
        '*/v2/*' => Http::response(['id' => 1, 'name' => 'Updated']),
    ]);

    $result = $this->client->patch('/Projects/1', [
        ['op' => 'replace', 'path' => '/Name', 'value' => 'Updated'],
    ]);

    expect($result)->toBe(['id' => 1, 'name' => 'Updated']);

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && str_contains($request->url(), '/Projects/1'));
});

it('returns customers resource', function () {
    expect($this->client->customers())->toBeInstanceOf(CustomerResource::class);
});

it('returns products resource', function () {
    expect($this->client->products())->toBeInstanceOf(ProductResource::class);
});

it('returns projects resource', function () {
    expect($this->client->projects())->toBeInstanceOf(ProjectResource::class);
});

it('returns sales orders resource', function () {
    expect($this->client->salesOrders())->toBeInstanceOf(SalesOrderResource::class);
});
