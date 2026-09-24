<?php 

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Diversworld\ContaoIssueServiceBundle\Repository\SqlSortWhitelist;

final class SqlSortWhitelistTest extends TestCase
{
    public function testUnknownFieldFallsBack(): void
    {
        self::assertSame(['last_public_activity_at', 'DESC'], (new SqlSortWhitelist())->normalize('1; DROP TABLE', 'sideways'));
    }

    public function testAllowsKnownField(): void
    {
        self::assertSame(['created_at', 'ASC'], (new SqlSortWhitelist())->normalize('created_at', 'asc'));
    }
}
