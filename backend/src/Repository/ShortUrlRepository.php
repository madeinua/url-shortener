<?php

namespace App\Repository;

use App\Entity\ShortUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShortUrlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShortUrl::class);
    }

    public function findOneByCode(string $code): ?ShortUrl
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findOneByCanonicalUrl(string $canonicalUrl): ?ShortUrl
    {
        return $this->findOneBy(['canonicalUrl' => $canonicalUrl]);
    }
}