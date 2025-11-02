<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

final readonly class ClickStats
{
    public function __construct(private Connection $db)
    {
    }

    public function incrementClicks(string $code): void
    {
        $this->db->executeStatement(
            'UPDATE short_urls
             SET clicks = clicks + 1,
                 last_accessed_at = NOW()
             WHERE code = :code',
            ['code' => $code],
        );
    }
}