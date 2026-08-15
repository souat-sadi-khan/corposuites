<?php

namespace App\Services;

use App\Models\Client;

class ClientService
{
    public function create(array $data): Client
    {
        $data['client_code'] = $this->generateClientCode();

        return Client::create($data);
    }

    public function update(Client $client, array $data): Client
    {
        // The client code is issued once and is referenced by every project
        // filed under it — a re-issued code would break that trail.
        unset($data['client_code']);

        $client->update($data);

        return $client->fresh();
    }

    public function delete(Client $client): bool
    {
        return $client->delete();
    }

    protected function generateClientCode(): string
    {
        $lastId = Client::max('id') ?? 0;

        return 'CLI-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
