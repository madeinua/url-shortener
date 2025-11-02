<?php

namespace App\Controller;

use App\Repository\ShortUrlRepository;
use App\Service\ClickStats;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class RedirectController
{
    public function __construct(
        private ShortUrlRepository $repo,
        private ClickStats $stats,
        private CacheInterface $cache
    ) {
    }

    #[Route('/r/{code}', name: 'redirect_code', methods: ['GET'])]
    public function __invoke(string $code): Response
    {
        $target = $this->cache->get('surl.code.' . $code, function (ItemInterface $item) use ($code) {
            $item->expiresAfter(3600);
            $e = $this->repo->findOneByCode($code);
            if (!$e) {
                $item->expiresAfter(10);
                return null;
            }
            return $e->getCanonicalUrl();
        });

        if ($target === null) {
            return new Response('Not found', 404);
        }

        $this->stats->incrementClicks($code);

        $response = new RedirectResponse($target, 302);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }
}
