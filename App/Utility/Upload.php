<?php

namespace App\Utility;

/**
 * Gestionnaire d'upload de fichiers
 *
 * Valide et déplace les fichiers uploadés vers le répertoire de stockage.
 *
 * PHP version 7.0
 */
class Upload
{
    /** Taille maximale autorisée pour un fichier uploadé (4 Mo) */
    private const MAX_FILE_SIZE = 4_000_000;

    /** Extensions de fichiers image autorisées */
    private const ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png'];

    /** Répertoire de destination relatif à la racine publique */
    private const UPLOAD_DIRECTORY = '/storage/';

    /**
     * Valide et déplace un fichier uploadé vers le répertoire de stockage.
     *
     * Le fichier est renommé avec l'identifiant fourni en tant que nom de base,
     * en conservant l'extension d'origine.
     *
     * @param array<string, mixed> $file     Entrée $_FILES correspondante au fichier
     * @param int|string           $fileName Nom de base du fichier de destination (sans extension)
     *
     * @return string Le nom du fichier stocké (avec extension)
     * @throws \Exception Si le fichier est absent, invalide, trop volumineux ou si le déplacement échoue
     */
    public static function uploadFile($file, $fileName): string
    {
        if (!is_array($file) || !isset($file['error'])) {
            throw new \Exception("Aucun fichier fourni.");
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new \Exception("Aucun fichier uploadé.");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Erreur lors de l'upload (code " . $file['error'] . ").");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \Exception("Extension non autorisée. Formats acceptés : JPEG, PNG.");
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new \Exception("Le fichier dépasse la taille maximale autorisée (4 Mo).");
        }

        $pictureName = basename($fileName . '.' . $extension);
        $uploadPath  = getcwd() . self::UPLOAD_DIRECTORY . $pictureName;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new \Exception("Une erreur est survenue lors du déplacement du fichier. Veuillez contacter l'administrateur.");
        }

        return $pictureName;
    }
}
