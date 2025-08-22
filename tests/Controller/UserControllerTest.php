<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();

        // Optionnel : vider les tables avant chaque test pour isolation
        $connection = $this->em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function getAdminToken(): string
    {
        $repo = $this->em->getRepository(User::class);
        $admin = $repo->findOneBy(['email'=>'admin@example.com']);
        if (!$admin) {
            $admin = new User();
            $admin->setName('Admin')
                  ->setEmail('admin@example.com')
                  ->setRoles(['ROLE_ADMIN'])
                  ->setPassword(
                      $this->client->getContainer()
                                   ->get('security.password_hasher')
                                   ->hashPassword($admin, 'password')
                  )
                  ->setActive(true);
            $this->em->persist($admin);
            $this->em->flush();
        }

        // Login
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE'=>'application/json'],
            json_encode(['email'=>'admin@example.com','password'=>'password'])
        );

        $resp = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $resp);
        return $resp['token'];
    }

    public function testCreateUser(): void
    {
        $token = $this->getAdminToken();

        // Fichier factice
        $filePath = sys_get_temp_dir() . '/avatar.png';
        file_put_contents($filePath, 'fake content');
        $uploadedFile = new UploadedFile($filePath, 'avatar.png', 'image/png', null, true);

        // Créer utilisateur
        $this->client->request(
            'POST',
            '/api/users',
            [
                'name'=>'John Doe',
                'email'=>'john@example.com',
                'password'=>'password123',
                'active'=>'true',
                'roles' => ['ROLE_USER','ROLE_ADMIN']
            ],
            ['photo'=>$uploadedFile],
            ['HTTP_Authorization'=>'Bearer '.$token]
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisateur créé', $response['message']);

        unlink($filePath);
    }

    public function testUpdateUserSuccess()
    {
        $token = $this->getAdminToken();

        // Créer un utilisateur à modifier
        $user = new User();
        $user->setName('Before Update')
             ->setEmail('update@example.com')
             ->setRoles(['ROLE_USER'])
             ->setPassword(
                 $this->client->getContainer()->get('security.password_hasher')->hashPassword($user, 'password123')
             )
             ->setActive(true);
        $this->em->persist($user);
        $this->em->flush();

        // Modifier le profil via /api/me
        $this->client->request(
            'POST',
            '/api/me',
            [
                'name' => 'After Update',
                'roles' => ['ROLE_ADMIN']
            ],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $resp = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Profil mis à jour avec succès', $resp['message']);
    }

    public function testListUsers(): void
    {
        $token = $this->getAdminToken();

        // Créer plusieurs utilisateurs
        for($i=1;$i<=3;$i++){
            $user = new User();
            $user->setName("User $i")
                 ->setEmail("user$i@example.com")
                 ->setRoles(['ROLE_USER'])
                 ->setPassword(
                     $this->client->getContainer()->get('security.password_hasher')
                                  ->hashPassword($user,'password123')
                 )
                 ->setActive(true);
            $this->em->persist($user);
        }
        $this->em->flush();

        $this->client->request(
            'GET',
            '/api/users?page=1&limit=2',
            [],
            [],
            ['HTTP_Authorization'=>'Bearer '.$token]
        );

        $this->assertResponseIsSuccessful();
        $resp = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $resp['data']); // pagination limit=2
    }

    public function testDeleteUser(): void
    {
        $token = $this->getAdminToken();

        $user = new User();
        $user->setName('To Delete')
             ->setEmail('todelete@example.com')
             ->setRoles(['ROLE_USER'])
             ->setPassword(
                 $this->client->getContainer()->get('security.password_hasher')
                              ->hashPassword($user,'password123')
             )
             ->setActive(true);
        $this->em->persist($user);
        $this->em->flush();

        $this->client->request(
            'DELETE',
            '/api/users/'.$user->getId(),
            [],
            [],
            ['HTTP_Authorization'=>'Bearer '.$token]
        );

        $this->assertResponseStatusCodeSame(200);
        $resp = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisateur supprimé', $resp['message']);
    }
}
