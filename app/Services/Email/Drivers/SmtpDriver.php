<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email as MimeEmail;

class SmtpDriver implements EmailDriver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * আসল SMTP handshake (EHLO/STARTTLS/AUTH) করে কানেকশন যাচাই করে —
     * শুধু socket খোলা না, credentials ভুল হলেও এখানে ধরা পড়বে।
     */
    public function testConnection(): array
    {
        $start = microtime(true);

        try {
            $transport = $this->buildTransport();
            $transport->start();
            $transport->stop();

            return [
                'success' => true,
                'message' => 'SMTP connection successful',
                'latency' => microtime(true) - $start,
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
                'latency' => microtime(true) - $start,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage(),
                'latency' => microtime(true) - $start,
            ];
        }
    }

    /**
     * দেওয়া config দিয়ে সত্যিকারের একটা টেস্ট ইমেইল পাঠায়।
     */
    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        try {
            $transport = $this->buildTransport();
            $mailer = new Mailer($transport);

            $email = (new MimeEmail())
                ->from($fromName ? sprintf('%s <%s>', $fromName, $from) : $from)
                ->to($to)
                ->subject($subject)
                ->text($message)
                ->html(nl2br(e($message)));

            $mailer->send($email);

            return [
                'success' => true,
                'message' => 'SMTP test email sent successfully',
                'response' => null,
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'message' => 'SMTP send failed',
                'error' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'SMTP send failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getMailConfig(): array
    {
        return [
            'driver' => 'smtp',
            'host' => $this->config['host'] ?? 'smtp.mailtrap.io',
            'port' => $this->config['port'] ?? 587,
            'username' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
            'encryption' => $this->config['encryption'] ?? 'tls',
            'timeout' => $this->config['timeout'] ?? 30,
            'auth_mode' => $this->config['auth_mode'] ?? null,
            'verify_peer' => $this->config['verify_peer'] ?? true,
        ];
    }

    /**
     * Dynamic config থেকে Symfony EsmtpTransport বানায় (Laravel mail config
     * টাচ না করেই, যেকোনো provider-এর জন্য reusable)।
     */
    protected function buildTransport(): EsmtpTransport
    {
        $host = $this->config['host'] ?? '';
        $port = (int) ($this->config['port'] ?? 587);
        $encryption = $this->config['encryption'] ?? 'tls';
        $timeout = (int) ($this->config['timeout'] ?? 30);
        $verifyPeer = filter_var($this->config['verify_peer'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (empty($host)) {
            throw new \InvalidArgumentException('SMTP host is required');
        }

        $tls = $encryption === 'ssl' ? true : ($port === 465 ? true : false);

        $transport = new EsmtpTransport($host, $port, $tls);
        $transport->getStream()->setTimeout($timeout);

        if (! empty($this->config['username'])) {
            $transport->setUsername($this->config['username']);
        }

        if (! empty($this->config['password'])) {
            $transport->setPassword($this->config['password']);
        }

        if (! $verifyPeer) {
            $transport->getStream()->setStreamOptions([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
        }

        return $transport;
    }
}
