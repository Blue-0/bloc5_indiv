<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Upload;
use Core\View;

/**
 * Contrôleur produit
 *
 * Gère l'ajout d'une annonce (avec photo) et l'affichage
 * de la fiche produit avec son formulaire de contact vendeur.
 *
 * PHP version 7.0
 */
class Product extends \Core\Controller
{
    /**
     * Affiche et traite le formulaire d'ajout d'une annonce.
     *
     * @return void
     * @throws \Exception En cas d'erreur d'upload ou de sauvegarde en base
     */
    public function indexAction(): void
    {
        if (isset($_POST['submit'])) {
            try {
                $formData = $_POST;

                if (!isset($_FILES['picture']) || $_FILES['picture']['error'] === UPLOAD_ERR_NO_FILE) {
                    throw new \Exception("Image obligatoire.");
                }
                if ($_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception("Erreur lors de l'upload (code " . $_FILES['picture']['error'] . ").");
                }

                // TODO: Validation complète des champs texte (name, description)

                $formData['user_id'] = $_SESSION['user']['id'];
                $articleId = Articles::save($formData);

                $pictureName = Upload::uploadFile($_FILES['picture'], $articleId);
                Articles::attachPicture($articleId, $pictureName);

                header('Location: /product/' . $articleId);
                exit;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        View::renderTemplate('Product/Add.html');
    }

    /**
     * Affiche la fiche d'un produit et traite le formulaire de contact vendeur.
     *
     * @return void
     */
    public function showAction(): void
    {
        $articleId  = $this->routeParams['id'];
        $mailStatus = null;
        $mailError  = null;

        try {
            Articles::addOneView($articleId);
            $suggestions = Articles::getSuggest();
            $article     = Articles::getOne($articleId);

            if (isset($_POST['submit_contact'])) {
                $buyerEmail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                $message    = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

                if (!$buyerEmail) {
                    throw new \Exception("Adresse e-mail invalide.");
                }
                if (empty($message)) {
                    throw new \Exception("Le message ne peut pas être vide.");
                }

                $sellerEmail = $article[0]['email'];
                $sellerName  = $article[0]['username'];
                $subject     = "[Vide Grenier] Nouveau message pour votre annonce : " . $article[0]['name'];

                $imagePath = null;
                $imgCell   = '';
                if (!empty($article[0]['picture'])) {
                    $possiblePath = dirname(__DIR__, 2) . '/public/storage/' . $article[0]['picture'];
                    if (file_exists($possiblePath)) {
                        $imagePath = $possiblePath;
                        $imgCell   = '<td style="width: 110px; vertical-align: top; padding-right: 15px;">'
                                   . '<img src="cid:product_img" style="width: 100px; height: 100px; '
                                   . 'object-fit: cover; border-radius: 6px; border: 1px solid #ddd;" '
                                   . 'alt="' . htmlspecialchars($article[0]['name']) . '" />'
                                   . '</td>';
                    }
                }

                $body = $this->buildContactEmailBody($article[0], $buyerEmail, $message, $imgCell);

                \App\Utility\Mailer::send($sellerEmail, $sellerName, $buyerEmail, $subject, $body, $imagePath);
                $mailStatus = 'success';
            }
        } catch (\Exception $e) {
            $mailStatus = 'error';
            $mailError  = $e->getMessage();
        }

        View::renderTemplate('Product/Show.html', [
            'article'     => $article[0],
            'suggestions' => $suggestions,
            'mailStatus'  => $mailStatus,
            'mailError'   => $mailError,
        ]);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Construit le corps HTML de l'e-mail de contact envoyé au vendeur.
     *
     * @param array<string, string> $article    Données de l'article concerné
     * @param string                $buyerEmail Adresse e-mail de l'acheteur
     * @param string                $message    Message saisi par l'acheteur
     * @param string                $imgCell    Fragment HTML <td> contenant l'image du produit (peut être vide)
     *
     * @return string Le corps HTML de l'e-mail
     */
    private function buildContactEmailBody(array $article, string $buyerEmail, string $message, string $imgCell): string
    {
        $articleName   = htmlspecialchars($article['name']);
        $sellerName    = htmlspecialchars($article['username']);
        $description   = htmlspecialchars(substr($article['description'], 0, 150));
        $descSuffix    = strlen($article['description']) > 150 ? '...' : '';
        $buyerEmailHtml = htmlspecialchars($buyerEmail);
        $messageHtml   = nl2br(htmlspecialchars($message));

        return <<<HTML
        <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
            <div style="background-color: #007bff; color: white; padding: 20px; text-align: center;">
                <h1 style="margin: 0; font-size: 24px;">Vide Grenier en Ligne</h1>
            </div>
            <div style="padding: 20px;">
                <h2 style="color: #007bff; margin-top: 0; font-size: 20px;">Nouveau message d'un acheteur</h2>
                <p>Bonjour <strong>{$sellerName}</strong>,</p>
                <p>Un utilisateur s'intéresse à votre annonce <strong>"{$articleName}"</strong> :</p>

                <table style="width: 100%; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 15px; margin-bottom: 20px; border-collapse: collapse;">
                    <tr>
                        {$imgCell}
                        <td style="vertical-align: top;">
                            <h4 style="margin: 0 0 5px 0; color: #333; font-size: 16px;">{$articleName}</h4>
                            <p style="margin: 0; font-size: 13px; color: #6c757d;">{$description}{$descSuffix}</p>
                        </td>
                    </tr>
                </table>

                <div style="background-color: #f1f3f5; border-left: 4px solid #007bff; padding: 15px; border-radius: 0 4px 4px 0; margin-bottom: 20px;">
                    <p style="margin: 0 0 10px 0; font-size: 13px; color: #495057;"><strong>Message de {$buyerEmailHtml} :</strong></p>
                    <p style="margin: 0; font-style: italic; white-space: pre-line;">{$messageHtml}</p>
                </div>

                <p style="font-size: 13px; color: #868e96; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px;">
                    <em>Pour lui répondre, cliquez simplement sur le bouton "Répondre" de votre boîte mail.</em>
                </p>
            </div>
        </div>
        HTML;
    }
}
