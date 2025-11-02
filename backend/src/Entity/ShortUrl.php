<?php

namespace App\Entity;

use App\Repository\ShortUrlRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShortUrlRepository::class)]
#[ORM\Table(name: 'short_urls')]
class ShortUrl
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 12, unique: true)]
    private string $code;

    #[ORM\Column(length: 2048, unique: true)]
    private string $canonicalUrl;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $clicks = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastAccessedAt = null;

    public function __construct(string $code, string $canonicalUrl)
    {
        $this->code = $code;
        $this->canonicalUrl = $canonicalUrl;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCanonicalUrl(): string
    {
        return $this->canonicalUrl;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastAccessedAt(): ?\DateTimeImmutable
    {
        return $this->lastAccessedAt;
    }

    public function touchAccessed(): void
    {
        $this->clicks++;
        $this->lastAccessedAt = new \DateTimeImmutable('now');
    }
}
