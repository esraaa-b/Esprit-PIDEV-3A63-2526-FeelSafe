<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class JournalReminderEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $emailExpediteur = 'noreply@feelsafe.com'
    ) {}

    public function sendReminderEmail(Utilisateur $user): void
    {
        $prenom = $user->getPrenom();
        $html   = $this->buildHtml($prenom);

        try {
            $email = (new Email())
                ->from(new Address($this->emailExpediteur, 'FeelSafe'))
                ->to(new Address($user->getEmail(), $prenom))
                ->subject("Votre journal vous attend, {$prenom} 📝")
                ->html($html);

            $this->mailer->send($email);
            $this->logger->info("✅ Reminder email sent to {$user->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("❌ Failed to send reminder: " . $e->getMessage());
        }
    }

    private function buildHtml(string $prenom): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#16a34a,#4ade80);padding:36px 40px;text-align:center;">
        <p style="margin:0;font-size:32px;">&#x2665;</p>
        <h1 style="margin:8px 0 0;color:#ffffff;font-size:26px;letter-spacing:1px;">FeelSafe</h1>
        <p style="margin:6px 0 0;color:#dcfce7;font-size:14px;">Votre espace de bien-être</p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;">
        <h2 style="margin:0 0 16px;color:#111827;font-size:20px;">Bonjour {$prenom} &#x1F44B;</h2>
        <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.7;">
          Nous avons remarqu&eacute; que vous n'avez pas &eacute;crit dans votre journal
          <strong>depuis 2 jours</strong>.
          Votre parcours &eacute;motionnel nous tient &agrave; c&oelig;ur &#x1F499;
        </p>

        <!-- Quote box -->
        <div style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:8px;padding:16px 20px;margin:24px 0;">
          <p style="margin:0;color:#15803d;font-size:15px;font-style:italic;line-height:1.6;">
            &#x201C; &Eacute;crire ce que l'on ressent, c'est d&eacute;j&agrave; prendre soin de soi. &#x201D;
          </p>
        </div>

        <p style="margin:0 0 28px;color:#374151;font-size:15px;line-height:1.7;">
          Prenez 2 minutes aujourd'hui pour noter votre humeur.
          Chaque entr&eacute;e compte dans votre chemin vers le mieux-&ecirc;tre &#x1F331;
        </p>

        <!-- CTA Button -->
        <div style="text-align:center;margin:32px 0;">
          <a href="#" style="background:#16a34a;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:50px;font-size:15px;font-weight:bold;display:inline-block;">
            &#x270F; &Eacute;crire dans mon journal
          </a>
        </div>

        <!-- Emotion row -->
        <p style="text-align:center;color:#6b7280;font-size:13px;margin:0 0 8px;">
          Comment vous sentez-vous aujourd'hui ?
        </p>
        <p style="text-align:center;font-size:28px;margin:0;letter-spacing:8px;">
          &#x1F600; &#x1F642; &#x1F610; &#x1F615; &#x1F622;
        </p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="margin:0 0 6px;color:#9ca3af;font-size:12px;">
          Vous recevez cet email car vous &ecirc;tes inscrit(e) sur FeelSafe.
        </p>
        <p style="margin:0;color:#9ca3af;font-size:12px;">
          Pour ne plus recevoir ces rappels, d&eacute;sactivez les notifications dans votre profil.
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>

</body>
</html>
HTML;
    }
}