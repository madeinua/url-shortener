<?php
namespace App\Tests\Unit;

use App\Service\CodeGenerator;
use PHPUnit\Framework\TestCase;

final class CodeGeneratorTest extends TestCase
{
    public function testDeterminismAndPrefixStability(): void
    {
        $g = new CodeGenerator();
        $u = 'https://example.com/path?a=1&b=2';

        $c7 = $g->generate($u, 7);
        $c8 = $g->generate($u, 8);
        $c10 = $g->generate($u, 10);

        $this->assertSame(7, strlen($c7));
        $this->assertSame(8, strlen($c8));
        $this->assertSame(10, strlen($c10));

        $this->assertSame($c7, substr($c8, 0, 7));
        $this->assertSame($c7, substr($c10, 0, 7));

        $this->assertSame($c7, $g->generate($u, 7));
        $this->assertSame($c8, $g->generate($u, 8));
        $this->assertSame($c10, $g->generate($u, 10));
    }
}
