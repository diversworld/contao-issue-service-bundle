<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\EventListener;

use Contao\{DataContainer, Input};
use Contao\CoreBundle\Slug\Slug;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\{IssueServiceListener, IssueCategoryListener, IssueStatusListener};
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class AliasGenerationTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['FORM_SUBMIT', 'title', 'service_id'] as $field) Input::setPost($field, null);
    }

    public function testEmptyAliasesUseSubmittedTitleAndCategoryScope(): void
    {
        foreach ([['tl_issue_service', IssueServiceListener::class, 'alias'], ['tl_issue_category', IssueCategoryListener::class, 'alias'], ['tl_issue_status', IssueStatusListener::class, 'status_key']] as [$table, $class, $field]) {
            Input::setPost('FORM_SUBMIT', $table);
            Input::setPost('title', 'Neuer Titel');
            Input::setPost('service_id', '7');
            $dc = $this->createStub(DataContainer::class);
            $dc->method('getCurrentRecord')->willReturn(['title' => 'Alter Titel', 'service_id' => 2]);
            $db = $this->createMock(Connection::class);
            $db->expects(self::once())->method('fetchOne')->willReturnCallback(static function ($sql, $params) use ($table, $field): bool {
                self::assertStringContainsString($table, $sql);
                self::assertStringContainsString($field.'=?', $sql);
                if ($table === 'tl_issue_category') self::assertSame('7', $params[2]);
                return false;
            });
            $slug = $this->createMock(Slug::class);
            $slug->expects(self::once())->method('generate')->with('Neuer Titel', [], self::isCallable())->willReturnCallback(static function ($title, $options, $exists): string {
                self::assertFalse($exists('neuer-titel'));
                return 'neuer-titel';
            });
            self::assertSame('neuer-titel', (new $class($db, $slug))->generateAlias('', $dc));
        }
    }

    public function testManualAliasIsPreservedAndDuplicatesRejected(): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc->method('getCurrentRecord')->willReturn(['title' => 'Titel']);
        $slug = $this->createMock(Slug::class);
        $slug->expects(self::never())->method('generate');
        $db = $this->createStub(Connection::class);
        $db->method('fetchOne')->willReturnOnConsecutiveCalls(false, 9);
        $listener = new IssueServiceListener($db, $slug);
        self::assertSame('manuell', $listener->generateAlias('manuell', $dc));
        $this->expectException(\Exception::class);
        $listener->generateAlias('manuell', $dc);
    }
}
