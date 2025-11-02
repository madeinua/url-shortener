<?php
namespace App\Tests\Functional;

final class ShorteningApiTest extends FunctionalWebTestCase
{
    public function testShortenAndRedirect(): void
    {
        $this->client->request('POST', '/api/urls',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['url' => 'https://example.com/path?a=1&b=2'])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $code = $data['code'];

        $this->client->request('GET', '/r/' . $code);
        $this->assertResponseStatusCodeSame(302);
    }
}
