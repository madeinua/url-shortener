<?php

namespace App\Service;

use App\Entity\ShortUrl;
use App\Repository\ShortUrlRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ShorteningService
{
    public function __construct(
        private UrlCanonicalizer $canonicalizer,
        private CodeGenerator $codes,
        private ShortUrlRepository $repo,
        private EntityManagerInterface $em,
    ) {
    }

    public function shorten(string $inputUrl): ShortUrl
    {
        $canonical = $this->canonicalizer->canonicalize($inputUrl);
        return $this->shortenCanonical($canonical);
    }

    public function shortenCanonical(string $canonical): ShortUrl
    {
        $existing = $this->repo->findOneByCanonicalUrl($canonical);
        if ($existing) {
            return $existing;
        }

        for ($len = 7; $len <= 10; $len++) {
            $code = $this->codes->generate($canonical, $len);
            $conflict = $this->repo->findOneByCode($code);
            if ($conflict && $conflict->getCanonicalUrl() !== $canonical) {
                continue; // Try a longer code
            }

            $entity = new ShortUrl($code, $canonical);
            try {
                $this->em->persist($entity);
                $this->em->flush();
                return $entity;
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw new \RuntimeException('Unable to allocate a unique short code');
    }
}
