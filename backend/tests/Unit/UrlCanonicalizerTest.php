<?php
namespace App\Tests\Unit;

use App\Service\UrlCanonicalizer;
use PHPUnit\Framework\TestCase;

final class UrlCanonicalizerTest extends TestCase
{
    public function testCanonicalization(): void
    {
        $c = new UrlCanonicalizer();
        $u = $c->canonicalize(' HTTPS://ExAmPlE.com:443/Path//to?b=2&a=1#frag ');
        $this->assertSame('https://example.com/Path/to?a=1&b=2', $u);
    }

    public function testUserinfoIsRejected(): void
    {
        $c = new UrlCanonicalizer();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/credential|allowed/i');
        $c->canonicalize('https://user:pass@example.com/');
    }
}
