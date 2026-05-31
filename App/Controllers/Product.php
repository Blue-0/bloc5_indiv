<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Upload;
use \Core\View;

/**
 * Product controller
 */
class Product extends \Core\Controller
{

    /**
     * Affiche la page d'ajout
     * @return void
     */
    public function indexAction()
    {

        if(isset($_POST['submit'])) {

            try {
                $f = $_POST;

                if (!isset($_FILES['picture']) || $_FILES['picture']['error'] === UPLOAD_ERR_NO_FILE) {
                    throw new \Exception("Image obligatoire.");
                }
                if ($_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception("Erreur upload (code " . $_FILES['picture']['error'] . ").");
                }

                // TODO: Validation

                $f['user_id'] = $_SESSION['user']['id'];
                $id = Articles::save($f);

                $pictureName = Upload::uploadFile($_FILES['picture'], $id);

                Articles::attachPicture($id, $pictureName);

                header('Location: /product/' . $id);
            } catch (\Exception $e){
                    var_dump($e);
            }
        }

        View::renderTemplate('Product/Add.html');
    }

    /**
     * Affiche la page d'un produit et gère le formulaire de contact
     * @return void
     */
    public function showAction()
    {
        $id = $this->route_params['id'];
        $mailStatus = null;
        $mailError = null;

        try {
            Articles::addOneView($id);
            $suggestions = Articles::getSuggest();
            $article = Articles::getOne($id);

            if (isset($_POST['submit_contact'])) {
                $buyerEmail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

                if (!$buyerEmail) {
                    throw new \Exception("Adresse e-mail invalide.");
                }
                if (empty($message)) {
                    throw new \Exception("Le message ne peut pas être vide.");
                }

                $sellerEmail = $article[0]['email'];
                $sellerName = $article[0]['username'];
                $subject = "[Vide Grenier] Nouveau message pour votre annonce : " . $article[0]['name'];

                $imagePath = null;
                $imgCell = '';
                if (!empty($article[0]['picture'])) {
                    $possiblePath = dirname(__DIR__, 2) . '/public/storage/' . $article[0]['picture'];
                    if (file_exists($possiblePath)) {
                        $imagePath = $possiblePath;
                        $imgCell = '<td style="width: 110px; vertical-align: top; padding-right: 15px;">
                                        <img src="cid:product_img" style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;" alt="' . htmlspecialchars($article[0]['name']) . '" />
                                    </td>';
                    }
                }

                $body = '
                <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                    <div style="background-color: #007bff; color: white; padding: 20px; text-align: center;">
                        <h1 style="margin: 0; font-size: 24px;">Vide Grenier en Ligne</h1>
                    </div>
                    <div style="padding: 20px;">
                        <h2 style="color: #007bff; margin-top: 0; font-size: 20px;">Nouveau message d\'un acheteur</h2>
                        <p>Bonjour <strong>' . htmlspecialchars($sellerName) . '</strong>,</p>
                        <p>Un utilisateur s\'intéresse à votre annonce <strong>"' . htmlspecialchars($article[0]['name']) . '"</strong> :</p>
                        
                        <!-- Détails du produit -->
                        <table style="width: 100%; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 15px; margin-bottom: 20px; border-collapse: collapse;">
                            <tr>
                                ' . $imgCell . '
                                <td style="vertical-align: top;">
                                    <h4 style="margin: 0 0 5px 0; color: #333; font-size: 16px;">' . htmlspecialchars($article[0]['name']) . '</h4>
                                    <p style="margin: 0; font-size: 13px; color: #6c757d;">' . htmlspecialchars(substr($article[0]['description'], 0, 150)) . (strlen($article[0]['description']) > 150 ? '...' : '') . '</p>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Message de l\'acheteur -->
                        <div style="background-color: #f1f3f5; border-left: 4px solid #007bff; padding: 15px; border-radius: 0 4px 4px 0; margin-bottom: 20px;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; color: #495057;"><strong>Message de ' . htmlspecialchars($buyerEmail) . ' :</strong></p>
                            <p style="margin: 0; font-style: italic; white-space: pre-line;">' . nl2br(htmlspecialchars($message)) . '</p>
                        </div>
                        
                        <p style="font-size: 13px; color: #868e96; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px;">
                            <em>Pour lui répondre, cliquez simplement sur le bouton "Répondre" de votre boîte mail.</em>
                        </p>
                    </div>
                </div>
                ';

                \App\Utility\Mailer::send($sellerEmail, $sellerName, $buyerEmail, $subject, $body, $imagePath);
                $mailStatus = 'success';
            }
        } catch (\Exception $e) {
            $mailStatus = 'error';
            $mailError = $e->getMessage();
        }

        View::renderTemplate('Product/Show.html', [
            'article' => $article[0],
            'suggestions' => $suggestions,
            'mailStatus' => $mailStatus,
            'mailError' => $mailError
        ]);
    }
}
