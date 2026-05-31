<?php

namespace tests\Integration;

use PHPUnit\Framework\TestCase;
use Core\Model;
use App\Models\User;
use App\Utility\Hash;

class TestModelHelper extends Model
{
    public static function getTestDB()
    {
        return static::getDB();
    }
}

class UserIntegrationTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        // Si l'hôte 'db' n'est pas résolu (exécution hors Docker), on redirige vers localhost (127.0.0.1)
        if (getenv('DB_HOST') === 'db' && gethostbyname('db') === 'db') {
            putenv('DB_HOST=127.0.0.1');
        }

        $this->db = TestModelHelper::getTestDB();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function testUserCreationAndRetrieval()
    {
        // 1. Jeu de données défini avec email unique
        $salt = Hash::generateSalt(32);
        $email = 'integration_' . uniqid() . '@test.com';
        $userData = [
            'username' => 'test_user_integration',
            'email' => $email,
            'password' => Hash::generate('MySecurePassword123!', $salt),
            'salt' => $salt
        ];

        // 2. Création de l'utilisateur
        $userId = User::createUser($userData);
        $this->assertNotEmpty($userId);

        // 3. Récupération de l'utilisateur et validation
        $retrievedUser = User::getByLogin($email);
        $this->assertNotFalse($retrievedUser);
        $this->assertEquals($userData['username'], $retrievedUser['username']);
        $this->assertEquals($userData['email'], $retrievedUser['email']);
        $this->assertEquals($userData['password'], $retrievedUser['password']);
        $this->assertEquals($userData['salt'], $retrievedUser['salt']);
    }

    public function testUserCreationMissingEmailThrowsException()
    {
        // 1. Jeu de données avec valeur obligatoire manquante (email est NULL)
        $salt = Hash::generateSalt(32);
        $userData = [
            'username' => 'test_missing_email',
            'email' => null, // Email manquant, obligatoire en BDD (colonne NOT NULL)
            'password' => Hash::generate('password', $salt),
            'salt' => $salt
        ];

        // 2. Vérification que l'absence de valeur requise lève une exception PDO
        $this->expectException(\PDOException::class);
        User::createUser($userData);
    }

    public function testUserCreationUsernameTooLongThrowsException()
    {
        // 1. Jeu de données avec dépassement de limite de longueur (VARCHAR(100))
        $salt = Hash::generateSalt(32);
        $userData = [
            'username' => str_repeat('a', 105), // Dépasse les 100 caractères autorisés
            'email' => 'toolong@test.com',
            'password' => Hash::generate('password', $salt),
            'salt' => $salt
        ];

        // 2. Doit lever une exception pour dépassement de capacité (Data too long)
        $this->expectException(\PDOException::class);
        User::createUser($userData);
    }
}
