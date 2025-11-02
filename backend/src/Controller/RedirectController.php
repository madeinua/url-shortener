<?php

namespace App\Controller;

use App\Repository\ShortUrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final readonly class RedirectController
{
    public function __construct(
        private ShortUrlRepository $repo,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/r/{code}', name: 'redirect_code', methods: ['GET'])]
    public function __invoke(string $code): Response
    {
        $e = $this->repo->findOneByCode($code);
        if (!$e) {
            return new Response('Not found', 404);
        }

        $e->touchAccessed();
        $this->em->flush();

        return new RedirectResponse($e->getCanonicalUrl(), 302);
    }
}
