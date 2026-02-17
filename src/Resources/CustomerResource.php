<?php

namespace Tor2r\PowerOfficeApi\Resources;

use Tor2r\PowerOfficeApi\PowerOfficeClient;

class CustomerResource
{
    public function __construct(
        private readonly PowerOfficeClient $client,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        return $this->client->get("/Customers/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function getByOrgNr(int $orgNr): array
    {
        $filter = ['organizationNumbers' => $orgNr];

        return $this->client->get('/Customers', $filter);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/Customers', $filters);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/Customers', $data);
    }

    /**
     * @param  array<string, mixed>  $operations
     * @return array<string, mixed>
     */
    public function update(int $id, array $operations): array
    {
        return $this->client->patch("/Customers/{$id}", $operations);
    }
}
