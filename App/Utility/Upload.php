<?php

namespace App\Utility;

class Upload {


    public static function uploadFile($file, $fileName)
    {
        if (!is_array($file) || !isset($file['error'])) {
            throw new \Exception("No file was provided.");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                throw new \Exception("No file was uploaded.");
            }
            throw new \Exception("Upload error (code " . $file['error'] . ").");
        }

        $currentDirectory = getcwd();
        $uploadDirectory = "/storage/";


        $fileExtensionsAllowed = ['jpeg', 'jpg', 'png'];

        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $pictureName = basename($fileName . '.'. $fileExtension);


        $uploadPath = $currentDirectory . $uploadDirectory . $pictureName;

        if (!in_array($fileExtension, $fileExtensionsAllowed)) {
            throw new \Exception("This file extension is not allowed. Please upload a JPEG or PNG file");
        }

        if ($fileSize > 4000000) {
            throw new \Exception("File exceeds maximum size (4MB)");
        }

        $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

        if ($didUpload) {
            return $pictureName;
        } else {
            throw new \Exception("An error occurred. Please contact the administrator.");
        }
    }
}
