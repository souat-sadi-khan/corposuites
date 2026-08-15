<?php

namespace App\Services\Email;

use App\Models\Email\EmailProvider;
use App\Models\Email\EmailSenderIdentity;

class EmailService
{
    public function __construct(
        protected EmailProviderManager $providerManager,
        protected MailConfigurationService $configService,
        protected EmailConnectionTester $connectionTester,
        protected TestEmailService $testEmailService,
        protected ProviderHealthService $healthService,
        protected SenderIdentityService $identityService
    ) {}

    // Provider CRUD
    public function createProvider(array $data): array
    {
        return $this->providerManager->create($data);
    }

    public function updateProvider(string $id, array $data): array
    {
        return $this->providerManager->update($id, $data);
    }

    public function deleteProvider(string $id): array
    {
        return $this->providerManager->delete($id);
    }

    public function enableProvider(string $id): array
    {
        return $this->providerManager->enable($id);
    }

    public function disableProvider(string $id): array
    {
        return $this->providerManager->disable($id);
    }

    public function setDefaultProvider(string $id): array
    {
        return $this->providerManager->setDefault($id);
    }

    public function validateProviderConfig(string $type, array $config): array
    {
        return $this->providerManager->validateConfiguration($type, $config);
    }

    // Connection test
    public function testConnection(EmailProvider $provider): array
    {
        return $this->connectionTester->test($provider);
    }

    // Send test email
    public function sendTestEmail(
        EmailProvider $provider,
        EmailSenderIdentity $senderIdentity,
        string $recipient,
        string $subject = 'Test Email',
        string $message = 'This is a test email.'
    ): array {
        return $this->testEmailService->sendTest($provider, $senderIdentity, $recipient, $subject, $message);
    }

    // Health
    public function getProviderHealth(EmailProvider $provider): array
    {
        return $this->healthService->getHealth($provider);
    }

    // Configuration
    public function getMailConfig(EmailProvider $provider): array
    {
        return $this->configService->buildConfig($provider);
    }

    public function createSenderIdentity(array $data): array
    {
        return $this->identityService->create($data);
    }

    public function updateSenderIdentity(string $identityId, array $data): array
    {
        return $this->identityService->update($identityId, $data);
    }

    public function deleteSenderIdentity(string $identityId): array
    {
        return $this->identityService->delete($identityId);
    }

    public function setDefaultSenderIdentity(string $identityId): array
    {
        return $this->identityService->setDefault($identityId);
    }
}
