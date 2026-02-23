<?php

namespace App\Service;

use App\Entity\Urgence;
use App\Entity\Utilisateur;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EmergencyMailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $adminEmail;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $adminEmail = 'admin@feelsafe.com'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->adminEmail = $adminEmail;
    }

    public function sendEmergencyNotification(Urgence $emergency, Utilisateur $user): void
    {
        $this->logger->info('Sending admin notification email');

        try {
            // Map severity level to text
            $severityText = match ($emergency->getSeverityLevel()) {
                5 => 'CRITIQUE',
                4 => 'URGENT',
                3 => 'MODÉRÉ',
                2 => 'FAIBLE',
                1 => 'TRÈS FAIBLE',
                default => 'NON DÉFINI'
            };

            $email = (new TemplatedEmail())
                ->from(new Address('aoueslatii95@gmail.com', 'FeelSafe System'))
                ->to($this->adminEmail)
                ->subject('🚨 NOUVELLE URGENCE SIGNALÉE - ' . $severityText)
                ->htmlTemplate('emails/emergency_notification.html.twig')
                ->context([
                    'emergency' => $emergency,
                    'user' => $user,
                    'severityText' => $severityText,
                    'reportedAt' => new \DateTime(),
                ]);

            $email->replyTo($user->getEmail());
            $this->mailer->send($email);

            $this->logger->info('Admin notification email sent successfully');

        } catch (\Exception $e) {
            $this->logger->error('Failed to send admin email: ' . $e->getMessage());
        }
    }
}