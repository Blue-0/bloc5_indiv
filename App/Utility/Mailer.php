<?php

namespace App\Utility;

use App\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{

    /**
     * Envoie un e-mail en utilisant Mailtrap (ou un autre serveur SMTP configuré)
     *
     * @param string $to Adresse du destinataire (propriétaire de l'annonce)
     * @param string $toName Nom du destinataire
     * @param string $from Adresse de l'expéditeur (l'acheteur qui contacte)
     * @param string $subject Sujet de l'e-mail
     * @param string $body Contenu HTML du message
     * @return bool
     * @throws Exception
     */
    public static function send($to, $toName, $from, $subject, $body, $imagePath = null)
    {
        $mail = new PHPMailer(true);

        try {
            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: Config::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER') ?: Config::SMTP_USER;
            $mail->Password = getenv('SMTP_PASSWORD') ?: Config::SMTP_PASSWORD;
            $mail->Port = getenv('SMTP_PORT') ?: Config::SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            // Mailtrap accepte TLS ou pas de chiffrement sur le port 2525
            // On active STARTTLS pour la sécurité
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Destinataires
            // L'expéditeur 'From' est générique pour éviter d'être rejeté (car SPF/DKIM bloqueraient l'e-mail de l'acheteur)
            // Mais l'adresse de l'acheteur est mise dans Reply-To pour que le vendeur puisse y répondre directement.
            $mail->setFrom('sownligne@gmail.com', 'Vide Grenier en Ligne');
            $mail->addReplyTo($from);
            $mail->addAddress($to, $toName);

            // Intégration de l'image du produit si fournie
            if ($imagePath && file_exists($imagePath)) {
                $mail->addEmbeddedImage($imagePath, 'product_img');
            }

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            throw new \Exception("Erreur lors de l'envoi de l'e-mail : " . $mail->ErrorInfo);
        }
    }
}
