<?php

namespace Tor2r\PowerOfficeApi\Resources;

use Tor2r\PowerOfficeApi\PowerOfficeClient;

class ProjectResource
{
    public function __construct(
        private readonly PowerOfficeClient $client,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        return $this->client->get("/Projects/{$id}");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/Projects', $filters);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/Projects', $data);
    }

    /**
     * @param  array<string, mixed>  $operations
     * @return array<string, mixed>
     */
    public function update(int $id, array $operations): array
    {
        return $this->client->patch("/Projects/{$id}", $operations);
    }
}
