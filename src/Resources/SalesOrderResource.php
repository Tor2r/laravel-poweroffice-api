<?php

namespace Tor2r\PowerOfficeApi\Resources;

use Tor2r\PowerOfficeApi\PowerOfficeClient;

class SalesOrderResource
{
    public function __construct(
        private readonly PowerOfficeClient $client,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get("/SalesOrders/{$id}");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/SalesOrders', $filters);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/SalesOrders', $data);
    }
}
