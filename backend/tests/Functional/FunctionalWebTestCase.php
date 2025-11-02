<?php
namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;

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

        // Reset common keys just in case
        /** @var RateLimiterFactory $f */
        $f = static::getContainer()->get('limiter.shorten_ip');
        foreach (['127.0.0.1', '::1', 'anon'] as $k) {
            $f->create($k)->reset();
        }

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
