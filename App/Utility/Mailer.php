<?php

namespace App\Utility;

use App\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Utilitaire d'envoi d'e-mails
 *
 * Encapsule PHPMailer pour l'envoi d'e-mails HTML via SMTP (Mailtrap en développement).
 * Les paramètres de connexion sont lus depuis les variables d'environnement,
 * avec repli sur la configuration statique.
 *
 * PHP version 7.0
 */
class Mailer
{
    /**
     * Envoie un e-mail HTML via SMTP.
     *
     * L'adresse "From" est générique (domaine vérifié) pour éviter le rejet SPF/DKIM.
     * L'adresse de l'expéditeur réel est placée en "Reply-To" afin que le destinataire
     * puisse lui répondre directement.
     *
     * @param string      $to        Adresse du destinataire (vendeur)
     * @param string      $toName    Nom du destinataire
     * @param string      $from      Adresse de l'expéditeur (acheteur)
     * @param string      $subject   Sujet de l'e-mail
     * @param string      $body      Corps HTML du message
     * @param string|null $imagePath Chemin absolu vers une image à intégrer (facultatif)
     *
     * @return bool true si l'e-mail a été envoyé avec succès
     * @throws \Exception En cas d'échec SMTP
     */
    public static function send(
        string $to,
        string $toName,
        string $from,
        string $subject,
        string $body,
        ?string $imagePath = null
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST') ?: Config::SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER') ?: Config::SMTP_USER;
            $mail->Password   = getenv('SMTP_PASSWORD') ?: Config::SMTP_PASSWORD;
            $mail->Port       = getenv('SMTP_PORT') ?: Config::SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Expéditeur et destinataires
            $mail->setFrom('sownligne@gmail.com', 'Vide Grenier en Ligne');
            $mail->addReplyTo($from);
            $mail->addAddress($to, $toName);

            // Image intégrée (optionnelle)
            if ($imagePath && file_exists($imagePath)) {
                $mail->addEmbeddedImage($imagePath, 'product_img');
            }

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (PHPMailerException $e) {
            throw new \Exception("Erreur lors de l'envoi de l'e-mail : " . $mail->ErrorInfo);
        }
    }
}
