<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utility\Hash;

class HashTest extends TestCase
{
    public function testGenerateWithoutSalt()
    {
        $input = "password123";
        $expected = hash("sha256", $input);
        $this->assertEquals($expected, Hash::generate($input));
    }

    public function testGenerateWithSalt()
    {
        $input = "password123";
        $salt = "random_salt";
        $expected = hash("sha256", $input . $salt);
        $this->assertEquals($expected, Hash::generate($input, $salt));
    }

    public function testGenerateSaltLength()
    {
        $length = 16;
        $salt = Hash::generateSalt($length);
        $this->assertEquals($length, strlen($salt));
    }

    public function testGenerateSaltRandomness()
    {
        $salt1 = Hash::generateSalt(32);
        $salt2 = Hash::generateSalt(32);
        $this->assertNotEquals($salt1, $salt2);
    }

    public function testGenerateUnique()
    {
        $unique = Hash::generateUnique();
        $this->assertEquals(64, strlen($unique)); // Le hash SHA-256 fait toujours 64 caractères
        
        $unique2 = Hash::generateUnique();
        $this->assertNotEquals($unique, $unique2);
    }
}
