<?php 

declare(strict_types=1); 

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application; 
use PHPUnit\Framework\TestCase; 
use Diversworld\ContaoIssueServiceBundle\Application\TicketPattern; 

final class TicketPatternTest extends TestCase 
{ 
    public function testRenders():void
    {
        self::assertSame('IT-2026-000042',(new TicketPattern())->render('{SERVICE}-{YEAR}-{SEQ}','it',2026,42));
    }

    public function testRejectsUnknownPlaceholder():void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new TicketPattern())->render('{FOO}', 'it',2026,1);
    }
}
