<?php
namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        // IMPORTANT: let createClient() boot the kernel --> otherwise this triggers error
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $conn = $em->getConnection();

        try {
            $conn->executeStatement('TRUNCATE TABLE short_urls');
        } catch (\Throwable) {
            $conn->executeStatement('DELETE FROM short_urls');
            $conn->executeStatement('ALTER TABLE short_urls AUTO_INCREMENT = 1');
        }
    }
}
