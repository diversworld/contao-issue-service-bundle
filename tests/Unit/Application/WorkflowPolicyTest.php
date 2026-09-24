<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Diversworld\ContaoIssueServiceBundle\Application\WorkflowPolicy;
use Diversworld\ContaoIssueServiceBundle\Domain\Enum\IssueStatus;

final class WorkflowPolicyTest extends TestCase 
{
    #[DataProvider('allowed')]
    public function testAllows(IssueStatus $a, IssueStatus $b): void
    {
        self::assertTrue((new WorkflowPolicy())->allows($a, $b));
    }

    /** @return iterable<array{IssueStatus, IssueStatus}> */
    public static function allowed(): iterable
    {
        yield [IssueStatus::New, IssueStatus::Triage];
        yield [IssueStatus::InProgress, IssueStatus::Resolved];
        yield [IssueStatus::Closed, IssueStatus::InProgress];
    }

    public function testRejectsIllegalTransition(): void
    {
        $this->expectException(\DomainException::class);
        (new WorkflowPolicy())->assertAllowed(IssueStatus::New, IssueStatus::Closed);
    }
}
