<?php
namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $this->client->disableReboot();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $conn = $this->em->getConnection();

        if (!$conn->isTransactionActive()) {
            $conn->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        $conn = $this->em->getConnection();
        if ($conn->isTransactionActive()) {
            $conn->rollBack();
        }

        parent::tearDown();
        self::ensureKernelShutdown();
    }
}
