<?php
namespace App\Tests\Functional;

final class ApiBehaviorTest extends FunctionalWebTestCase
{
    public function testIdempotentShortening(): void
    {
        $payload = ['url' => 'https://example.com/path?a=1&b=2'];

        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload));
        $this->assertResponseStatusCodeSame(201);
        $first = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('code', $first);

        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload));
        $this->assertResponseStatusCodeSame(200);
        $second = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($first['code'], $second['code'], 'Same canonical URL must return the same code');
    }

    public function testStatsIncrementViaRedirect(): void
    {
        // Create short URL
        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'url' => 'https://example.com/a?x=1&y=2'
        ]));
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $code = $data['code'];

        // Initial stats
        $this->client->request('GET', '/api/urls/' . $code . '/stats');
        $this->assertResponseStatusCodeSame(200);
        $stats = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(0, $stats['clicks']);

        // Two redirects -> two clicks
        $this->client->request('GET', '/r/' . $code);
        $this->assertResponseStatusCodeSame(302);
        $this->assertNotEmpty($this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/r/' . $code);
        $this->assertResponseStatusCodeSame(302);

        // Stats reflect increments
        $this->client->request('GET', '/api/urls/' . $code . '/stats');
        $this->assertResponseStatusCodeSame(200);
        $stats = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $stats['clicks']);

        // Detail returns clicks too
        $this->client->request('GET', '/api/urls/' . $code);
        $this->assertResponseStatusCodeSame(200);
        $detail = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $detail['clicks']);
    }

    public function testListAllUrlsAndDetail(): void
    {
        // Create two different URLs
        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'url' => 'https://example.com/one'
        ]));
        $this->assertResponseStatusCodeSame(201);
        $one = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'url' => 'https://example.org/two'
        ]));
        $this->assertResponseStatusCodeSame(201);
        $two = json_decode($this->client->getResponse()->getContent(), true);

        // List
        $this->client->request('GET', '/api/urls?limit=50');
        $this->assertResponseIsSuccessful();
        $list = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($list);
        $codes = array_column($list, 'code');
        $this->assertContains($one['code'], $codes);
        $this->assertContains($two['code'], $codes);

        // Detail shape
        $this->client->request('GET', '/api/urls/' . $one['code']);
        $this->assertResponseIsSuccessful();
        $detail = json_decode($this->client->getResponse()->getContent(), true);
        foreach (['code', 'short_url', 'url', 'clicks', 'created_at'] as $k) {
            $this->assertArrayHasKey($k, $detail);
        }
    }

    public function testValidationErrors(): void
    {
        // Empty
        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['url' => '']));
        $this->assertResponseStatusCodeSame(400);

        // Disallowed scheme
        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['url' => 'ftp://example.com']));
        $this->assertResponseStatusCodeSame(400);

        // Too long input (>2048)
        $long = 'http://example.com/' . str_repeat('a', 2050);
        $this->client->request('POST', '/api/urls', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['url' => $long]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testUnknownCodeReturns404(): void
    {
        $this->client->request('GET', '/api/urls/NoSuchCode');
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/api/urls/NoSuchCode/stats');
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/r/NoSuchCode');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSelfRedirectIsRejected(): void
    {
        $sd = rtrim(static::getContainer()->getParameter('shortener_domain'), '/');
        $payload = ['url' => $sd . '/r/ABC1234'];

        $this->client->request(
            'POST',
            '/api/urls',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsStringIgnoringCase('self-redirect', $data['error']);
    }

    public function testUserinfoInUrlIsRejected(): void
    {
        $payload = ['url' => 'https://user:secret@example.com/hidden'];

        $this->client->request(
            'POST',
            '/api/urls',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsStringIgnoringCase('credential', $data['error']);
    }
}
