<?php

namespace tests\Integration;

use PHPUnit\Framework\TestCase;
use Core\Model;
use App\Models\User;
use App\Models\Articles;
use App\Utility\Hash;

class TestModelHelperArticles extends Model
{
    public static function getTestDB()
    {
        return static::getDB();
    }
}

class ArticleIntegrationTest extends TestCase
{
    private $db;
    private $userId;

    protected function setUp(): void
    {
        // Si l'hôte 'db' n'est pas résolu (exécution hors Docker), on redirige vers localhost (127.0.0.1)
        if (getenv('DB_HOST') === 'db' && gethostbyname('db') === 'db') {
            putenv('DB_HOST=127.0.0.1');
        }

        $this->db = TestModelHelperArticles::getTestDB();
        $this->db->beginTransaction();

        // Créer un utilisateur de test obligatoire pour pouvoir y lier les articles
        $salt = Hash::generateSalt(32);
        $this->userId = User::createUser([
            'username' => 'article_test_user',
            'email' => 'article_test@test.com',
            'password' => Hash::generate('MyPassword123!', $salt),
            'salt' => $salt
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function testSaveAndRetrieveArticle()
    {
        // 1. Jeu de données défini pour l'article
        $articleData = [
            'name' => 'Mon bel objet',
            'description' => 'Description longue de mon bel objet à vendre.',
            'user_id' => $this->userId
        ];

        // 2. Sauvegarde de l'article en base de données
        $articleId = Articles::save($articleData);
        $this->assertNotEmpty($articleId);

        // 3. Rattachement de l'image de l'annonce
        $pictureName = 'product_' . $articleId . '.png';
        Articles::attachPicture($articleId, $pictureName);

        // 4. Récupération et vérification de la cohérence des données
        $results = Articles::getOne($articleId);
        $this->assertCount(1, $results);
        
        $article = $results[0];
        $this->assertEquals($articleData['name'], $article['name']);
        $this->assertEquals($articleData['description'], $article['description']);
        $this->assertEquals($this->userId, $article['user_id']);
        $this->assertEquals($pictureName, $article['picture']);
        
        // Vérification de la date de publication automatique (date du jour au format Y-m-d)
        $this->assertEquals(date('Y-m-d'), $article['published_date']);
    }

    public function testSaveArticleWithInvalidUserThrowsException()
    {
        // 1. Jeu de données avec un user_id inexistant (limite / contrainte de clé étrangère)
        $articleData = [
            'name' => 'Objet orphelin',
            'description' => 'Cet objet a un faux utilisateur.',
            'user_id' => 999999 // User ID inexistant en base
        ];

        // 2. Doit lever une exception pour violation de clé étrangère
        $this->expectException(\PDOException::class);
        Articles::save($articleData);
    }

    public function testSaveArticleMissingFieldsThrowsException()
    {
        // 1. Jeu de données avec valeur manquante (name à null)
        $articleData = [
            'name' => null, // Name obligatoire (NOT NULL)
            'description' => 'Description sans nom.',
            'user_id' => $this->userId
        ];

        // 2. Doit lever une exception car le champ name ne peut pas être null
        $this->expectException(\PDOException::class);
        Articles::save($articleData);
    }
}
