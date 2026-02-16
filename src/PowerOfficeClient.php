<?php

namespace Tor2r\PowerOfficeApi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeApiException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeAuthException;
use Tor2r\PowerOfficeApi\Exceptions\PowerOfficeValidationException;
use Tor2r\PowerOfficeApi\Resources\CustomerResource;
use Tor2r\PowerOfficeApi\Resources\ProductResource;
use Tor2r\PowerOfficeApi\Resources\SalesOrderResource;

class PowerOfficeClient
{
    private const CACHE_KEY = 'poweroffice_access_token';

    public function __construct(
        private readonly string $appKey,
        private readonly string $clientKey,
        private readonly string $subscriptionKey,
        private readonly string $baseUrl,
        private readonly string $tokenUrl,
        private readonly int $tokenTtl = 900,
    ) {}

    /**
     * Authenticate with the PowerOffice API and return the access token.
     *
     * @throws PowerOfficeAuthException
     */
    public function authenticate(): string
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode("{$this->appKey}:{$this->clientKey}"),
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            ])
            ->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new PowerOfficeAuthException(
                'Failed to authenticate with PowerOffice API.',
                context: [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
                code: $response->status(),
            );
        }

        $token = $response->json('access_token');

        if (! $token) {
            throw new PowerOfficeAuthException(
                'PowerOffice API did not return an access token.',
                context: ['body' => $response->body()],
            );
        }

        return $token;
    }

    /**
     * Get a cached access token, authenticating if necessary.
     */
    public function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, $this->tokenTtl, fn () => $this->authenticate());
    }

    /**
     * Flush the cached access token.
     */
    public function flushToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->sendRequest('GET', $endpoint, ['query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->sendRequest('POST', $endpoint, ['json' => $data]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->sendRequest('PUT', $endpoint, ['json' => $data]);
    }

    public function customers(): CustomerResource
    {
        return new CustomerResource($this);
    }

    public function products(): ProductResource
    {
        return new ProductResource($this);
    }

    public function salesOrders(): SalesOrderResource
    {
        return new SalesOrderResource($this);
    }

    /**
     * Send an authenticated request with retry logic.
     *
     * Retries up to 3 times with exponential backoff on 401 (after flushing token),
     * 429, and 5xx responses.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws PowerOfficeApiException
     * @throws PowerOfficeAuthException
     * @throws PowerOfficeValidationException
     */
    private function sendRequest(string $method, string $endpoint, array $options = []): array
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        $response = Http::withToken($this->getAccessToken())
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            ])
            ->acceptJson()
            ->retry(
                times: 3,
                sleepMilliseconds: fn (int $attempt) => $attempt * 500,
                when: $this->shouldRetry(...),
                throw: false,
            )
            ->send($method, $url, $options);

        if ($response->status() === 422) {
            throw new PowerOfficeValidationException(
                'Validation error from PowerOffice API.',
                errors: $response->json('errors', []),
                response: $response,
                code: 422,
            );
        }

        if ($response->failed()) {
            throw new PowerOfficeApiException(
                "PowerOffice API request failed with status {$response->status()}.",
                response: $response,
                code: $response->status(),
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Determine whether a failed request should be retried.
     *
     * Retries on 401 (after refreshing the token), 429, and 5xx responses.
     */
    private function shouldRetry(\Throwable $exception, PendingRequest $request): bool
    {
        if (! $exception instanceof RequestException) {
            return false;
        }

        $status = $exception->response->status();

        if ($status === 401) {
            $this->flushToken();
            $request->withToken($this->getAccessToken());

            return true;
        }

        return $status === 429 || $status >= 500;
    }
}
