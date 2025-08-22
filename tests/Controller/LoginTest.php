<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;

class LoginTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();

        // Créer la table si nécessaire
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // Créer un utilisateur pour le login
        $userRepo = $this->em->getRepository(User::class);
        $user = $userRepo->findOneBy(['email'=>'testlogin@example.com']);
        if (!$user) {
            $user = new User();
            $user->setName('Test Login')
                 ->setEmail('testlogin@example.com')
                 ->setRoles(['ROLE_USER'])
                 ->setPassword(
                     $this->client->getContainer()
                                  ->get('security.password_hasher')
                                  ->hashPassword($user, 'password123')
                 )
                 ->setActive(true);
            $this->em->persist($user);
            $this->em->flush();
        }
    }

    public function testLoginSuccess()
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE'=>'application/json'],
            json_encode([
                'email'=>'testlogin@example.com',
                'password'=>'password123'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginFailure()
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE'=>'application/json'],
            json_encode([
                'email'=>'testlogin@example.com',
                'password'=>'wrongpassword'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $data);
        $this->assertEquals(401, $data['code']);
        $this->assertArrayHasKey('message', $data);
    }
}
