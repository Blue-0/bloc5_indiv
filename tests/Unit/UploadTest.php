<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utility\Upload;

class UploadTest extends TestCase
{
    public function testUploadFileNoFileProvided()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No file was provided.");
        
        Upload::uploadFile(null, "test_file");
    }

    public function testUploadFileNoFileUploaded()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No file was uploaded.");
        
        $file = [
            'error' => UPLOAD_ERR_NO_FILE
        ];
        
        Upload::uploadFile($file, "test_file");
    }

    public function testUploadFileGenericUploadError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Upload error (code " . UPLOAD_ERR_INI_SIZE . ").");
        
        $file = [
            'error' => UPLOAD_ERR_INI_SIZE
        ];
        
        Upload::uploadFile($file, "test_file");
    }

    public function testUploadFileInvalidExtension()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("This file extension is not allowed. Please upload a JPEG or PNG file");
        
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
        $this->expectExceptionMessage("File exceeds maximum size (4MB)");
        
        $file = [
            'error' => UPLOAD_ERR_OK,
            'size' => 5000000, // 5MB, dépasse la limite de 4MB (4000000 octets)
            'tmp_name' => '/tmp/phpXYZ',
            'name' => 'image.jpg'
        ];
        
        Upload::uploadFile($file, "test_file");
    }
}
