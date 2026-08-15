<?php

namespace App\Services;

use App\Models\EmailCommunication;

class EmailCommunicationService
{
    public function create(array $data): EmailCommunication
    {
        return EmailCommunication::create($data);
    }

    public function update(EmailCommunication $emailCommunication, array $data): EmailCommunication
    {
        $emailCommunication->update($data);
        return $emailCommunication;
    }

    public function delete(EmailCommunication $emailCommunication): bool
    {
        return $emailCommunication->delete();
    }
}
