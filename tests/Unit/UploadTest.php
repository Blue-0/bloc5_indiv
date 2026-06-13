<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utility\Upload;

class UploadTest extends TestCase
{
    public function testUploadFileNoFileProvided()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Aucun fichier fourni.");
        
        Upload::uploadFile(null, "test_file");
    }

    public function testUploadFileNoFileUploaded()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Aucun fichier uploadé.");
        
        $file = [
            'error' => UPLOAD_ERR_NO_FILE
        ];
        
        Upload::uploadFile($file, "test_file");
    }

    public function testUploadFileGenericUploadError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Erreur lors de l'upload (code " . UPLOAD_ERR_INI_SIZE . ").");
        
        $file = [
            'error' => UPLOAD_ERR_INI_SIZE
        ];
        
        Upload::uploadFile($file, "test_file");
    }

    public function testUploadFileInvalidExtension()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Extension non autorisée. Formats acceptés : JPEG, PNG.");
        
        $file = [
            'error' => UPLOAD_ERR_OK,
            'size' => 1000,
            'tmp_name' => '/tmp/phpXYZ',
            'name' => 'document.pdf'
        ];
        
        Upload::uploadFile($file, "test_file");
    }

    public function testUploadFileExceedsMaxSize()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Le fichier dépasse la taille maximale autorisée (4 Mo).");
        
        $file = [
            'error' => UPLOAD_ERR_OK,
            'size' => 5000000, // 5MB, dépasse la limite de 4MB (4000000 octets)
            'tmp_name' => '/tmp/phpXYZ',
            'name' => 'image.jpg'
        ];
        
        Upload::uploadFile($file, "test_file");
    }
}
