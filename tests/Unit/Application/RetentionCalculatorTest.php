<?php 

declare(strict_types=1); 
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application; 
use PHPUnit\Framework\TestCase; 
use Diversworld\ContaoIssueServiceBundle\Application\RetentionCalculator; 

final class RetentionCalculatorTest extends TestCase 
{ 
    public function testCutoff():void
    {
        self::assertSame('2026-08-22',(new RetentionCalculator())->cutoff(new \DateTimeImmutable('2026-09-21'),30)->format('Y-m-d'));
    }
}
