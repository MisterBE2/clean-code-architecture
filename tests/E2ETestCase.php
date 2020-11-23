<?php
declare(strict_types=1);

namespace App\Tests;

use App\Controller\DoctorEntity;
use App\Controller\SlotEntity;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class E2ETestCase extends WebTestCase
{
    private array $entitiesToTruncate = [
        DoctorEntity::class,
        SlotEntity::class
    ];

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createClient();
        $this->truncateEntities(
            $this->entitiesToTruncate
        );
    }

    protected function serviceFromContainer(string $serviceId)
    {
        return static::$kernel->getContainer()->get($serviceId);
    }

    private function truncateEntities(array $entities)
    {
        $connection = $this->getEntityManager()->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->getEntityManager()->getClassMetadata($entity)->getTableName()
            );
            $connection->executeStatement($query);
        }
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }
    private function getEntityManager(): EntityManager
    {
        return $this->serviceFromContainer('doctrine')->getManager();
    }





}
