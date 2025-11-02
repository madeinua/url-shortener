<?php

namespace App\Controller;

use App\Repository\ShortUrlRepository;
use App\Service\ShorteningService;
use App\Service\UrlCanonicalizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

#[Route('/api')]
final readonly class UrlController
{
    public function __construct(
        private ShorteningService $shortener,
        private ShortUrlRepository $repo,
        private UrlCanonicalizer $canonicalizer,
        private string $shortener_domain = 'http://localhost:8080',
        #[Autowire(service: 'limiter.shorten_ip')] private RateLimiterFactory $shortenLimiter,
    ) {
    }

    #[Route('/urls', name: 'api_shorten', methods: ['POST'])]
    public function shorten(Request $request): JsonResponse
    {
        // --- Rate-limit per IP ---
        $limiter = $this->shortenLimiter->create($request->getClientIp() ?? 'anon');
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            $headers = [
                'Retry-After' => max(1, $limit->getRetryAfter()->getTimestamp() - time())
            ];

            return new JsonResponse(['error' => 'Too many requests'], 429, $headers);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $url = preg_replace('/^\s+|\s+$/u', '', (string) ($data['url'] ?? ''));

        $validator = Validation::createValidator();
        $violations = $validator->validate($url, [
            new Assert\NotBlank(),
            new Assert\Url(['protocols' => ['http', 'https']]),
            new Assert\Length(max: 2048),
        ]);

        if (count($violations) > 0) {
            return new JsonResponse(['error' => (string) $violations], 400);
        }

        try {
            $canonical = $this->canonicalizer->canonicalize($url);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        if (stripos($canonical, rtrim($this->shortener_domain, '/') . '/r/') === 0) {
            return new JsonResponse(['error' => 'Self-redirects are not allowed'], 400);
        }

        $existing = $this->repo->findOneByCanonicalUrl($canonical);
        if ($existing) {
            return new JsonResponse($this->present($existing), 200);
        }

        try {
            $entity = $this->shortener->shortenCanonical($canonical);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        return new JsonResponse($this->present($entity), 201);
    }

    #[Route('/urls', name: 'api_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = max(1, (int) $request->query->get('limit', 50));
        $limit = min($limit, 200);
        $offset = max(0, (int) $request->query->get('offset', 0));

        $items = $this->repo->findBy([], ['id' => 'DESC'], $limit, $offset);

        return new JsonResponse(array_map(fn($e) => $this->present($e), $items));
    }

    #[Route('/urls/{code}', name: 'api_detail', methods: ['GET'])]
    public function detail(string $code): JsonResponse
    {
        $e = $this->repo->findOneByCode($code);
        if (!$e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        return new JsonResponse($this->present($e));
    }

    #[Route('/urls/{code}/stats', name: 'api_stats', methods: ['GET'])]
    public function stats(string $code): JsonResponse
    {
        $e = $this->repo->findOneByCode($code);
        if (!$e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        return new JsonResponse([
            'code'             => $e->getCode(),
            'clicks'           => $e->getClicks(),
            'created_at'       => $e->getCreatedAt()->format(DATE_ATOM),
            'last_accessed_at' => $e->getLastAccessedAt()?->format(DATE_ATOM),
        ]);
    }

    private function present(\App\Entity\ShortUrl $e): array
    {
        return [
            'code'             => $e->getCode(),
            'short_url'        => rtrim($this->shortener_domain, '/') . '/r/' . $e->getCode(),
            'url'              => $e->getCanonicalUrl(),
            'clicks'           => $e->getClicks(),
            'created_at'       => $e->getCreatedAt()->format(DATE_ATOM),
            'last_accessed_at' => $e->getLastAccessedAt()?->format(DATE_ATOM),
        ];
    }
}
