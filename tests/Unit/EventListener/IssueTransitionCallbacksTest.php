<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\EventListener;

use Contao\DataContainer;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\IssueTransitionCallbacks;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class IssueTransitionCallbacksTest extends TestCase
{
    private function createCallbacks(int $from, int $to, bool $duplicate): IssueTransitionCallbacks
    {
        $GLOBALS['TL_LANG']['tl_issue_transition']['sameStatus'] = 'Same status';
        $GLOBALS['TL_LANG']['tl_issue_transition']['duplicateRule'] = 'Duplicate rule';
        $db = $this->createStub(Connection::class);
        $db->method('fetchOne')->willReturn($duplicate ? 42 : false);
        $requests = new RequestStack();
        $requests->push(new Request([], ['FORM_SUBMIT' => 'tl_issue_transition', 'from_status_id' => $from, 'to_status_id' => $to, 'role_key' => 'agent']));
        return new IssueTransitionCallbacks($db, $requests);
    }

    public function testRejectsSameStatus(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Same status');
        $this->createCallbacks(1, 1, false)->validate('1', $this->createStub(DataContainer::class));
    }

    public function testRejectsDuplicateRule(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Duplicate rule');
        $this->createCallbacks(1, 2, true)->validate('2', $this->createStub(DataContainer::class));
    }

    public function testAllowsNewRule(): void
    {
        self::assertSame('2', $this->createCallbacks(1, 2, false)->validate('2', $this->createStub(DataContainer::class)));
    }
}
