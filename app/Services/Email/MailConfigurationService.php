<?php

namespace App\Services\Email;

use App\Models\Email\EmailProvider;
use App\Services\Email\Drivers\EmailDriverFactory;

class MailConfigurationService
{
    public function buildConfig(EmailProvider $provider): array
    {
        $driver = EmailDriverFactory::make($provider->type, $provider->config ?? []);
        return $driver->getMailConfig();
    }

    public function buildMailer(EmailProvider $provider): \Illuminate\Mail\Mailer
    {
        if ($provider->type !== 'smtp') {
            throw new \RuntimeException('Mailer building only supported for SMTP type');
        }
        $config = $this->buildConfig($provider);
        $transport = (new \Illuminate\Mail\TransportManager(app()))->createSymfonyTransport($config);
        return new \Illuminate\Mail\Mailer(app('view'), $transport, app('events'));
    }
}
