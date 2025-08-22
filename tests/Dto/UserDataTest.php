<?php
namespace App\Tests\DTO;

use PHPUnit\Framework\TestCase;
use App\DTO\UserData;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class UserDataTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
                            ->enableAttributeMapping()
                             ->getValidator();

    }

    public function testSettersAndGetters()
    {
        $userData = new UserData();

        $userData->setName('John Doe')
                 ->setEmail('john@example.com')
                 ->setRoles(['ROLE_USER','ROLE_ADMIN'])
                 ->setPassword('password123')
                 ->setActive(true);

        $this->assertEquals('John Doe', $userData->getName());
        $this->assertEquals('john@example.com', $userData->getEmail());
        $this->assertEquals(['ROLE_USER','ROLE_ADMIN'], $userData->getRoles());
        $this->assertEquals('password123', $userData->getPassword());
        $this->assertTrue($userData->isActive());
    }

    public function testValidation()
    {
        $userData = new UserData();
        $userData->setName('')             // invalide
                 ->setEmail('invalid');   // invalide
        $userData->setRoles([]);           // invalide si NotBlank ou Count >0

        $errors = $this->validator->validate($userData);

        $this->assertCount(3, $errors);

        $codes = [];
        foreach ($errors as $error) {
            $codes[] = $error->getCode();
        }

        $this->assertContains(NotBlank::IS_BLANK_ERROR, $codes); // pour name
        $this->assertContains(Email::INVALID_FORMAT_ERROR, $codes); // pour email
        $this->assertContains(NotBlank::IS_BLANK_ERROR, $codes); // pour roles si NotBlank
    }
}
