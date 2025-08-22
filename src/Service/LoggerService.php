<?php

namespace App\Service;

use App\Entity\Log;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LoggerService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function log(?User $user, string $action, ?string $details = null): void
    {
        $log = new Log();
        $log->setUser($user);
        $log->setAction($action);
        $log->setDetails($details);

        $this->em->persist($log);
        $this->em->flush();
    }
}
