<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\SettingsService;
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class SettingsProfileTest extends TestCase
{
    public function testProfilesRemainIndependentAndDefaultIsPreserved(): void
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE tl_issue_profile (id INTEGER PRIMARY KEY, max_files_per_issue INTEGER, allowed_extensions TEXT, mail_recipients TEXT, retention_days INTEGER)');
        $db->insert('tl_issue_profile', ['id' => 1, 'max_files_per_issue' => 5, 'allowed_extensions' => serialize(['pdf']), 'mail_recipients' => 'a@example.com', 'retention_days' => 730]);
        $db->insert('tl_issue_profile', ['id' => 2, 'max_files_per_issue' => 2, 'allowed_extensions' => serialize(['png', 'jpg']), 'mail_recipients' => "b@example.com\nc@example.com", 'retention_days' => 30]);
        $default = new SettingsService(new SettingsRepository($db));
        $second = $default->forProfile(2);
        self::assertSame(5, $default->int('max_files_per_issue', 0));
        self::assertSame(2, $second->int('max_files_per_issue', 0));
        self::assertSame(['png', 'jpg'], $second->json('allowed_extensions'));
        self::assertSame(['b@example.com', 'c@example.com'], $second->json('mail_recipients'));
        self::assertSame(['closed_days' => 30], $second->json('retention_policy'));
        self::assertSame(['pdf'], $default->json('allowed_extensions'));
        self::assertSame(['a@example.com'], $default->json('mail_recipients'));
        $this->expectException(\InvalidArgumentException::class);
        $default->forProfile(999)->json('allowed_extensions');
    }
}
