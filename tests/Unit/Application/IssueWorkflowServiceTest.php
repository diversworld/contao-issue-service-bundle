<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Contao\BackendUser;
use Contao\FrontendUser;
use Contao\DataContainer;
use Diversworld\ContaoIssueServiceBundle\Application\{IssueWorkflowService, NotificationService, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Repository\{IssueRepository, SettingsRepository};
use Diversworld\ContaoIssueServiceBundle\Security\WorkflowRoles;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\IssueWorkflowCallbacks;
use Doctrine\DBAL\{Connection, DriverManager};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class IssueWorkflowServiceTest extends TestCase
{
    private Connection $real;
    private Connection $db;
    private IssueWorkflowService $workflow;
    private TokenStorage $tokens;

    protected function setUp(): void
    {
        $this->real = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        foreach ([
            'tl_issue' => 'id INTEGER PRIMARY KEY, status_id INTEGER, service_id INTEGER, profile_id INTEGER, assigned_user_id INTEGER, member_id INTEGER, version INTEGER, ticket_number TEXT, title TEXT, tstamp INTEGER, updated_at TEXT, last_public_activity_at TEXT, resolved_at TEXT, closed_at TEXT, deleted_at TEXT',
            'tl_issue_status' => 'id INTEGER PRIMARY KEY, title TEXT, sort_order INTEGER DEFAULT 0, published INTEGER DEFAULT 1, is_closed INTEGER DEFAULT 0, is_resolved INTEGER DEFAULT 0',
            'tl_issue_transition' => 'from_status_id INTEGER, to_status_id INTEGER, role_key TEXT, require_public_comment INTEGER DEFAULT 0, published INTEGER DEFAULT 1',
            'tl_issue_history' => 'id INTEGER PRIMARY KEY, issue_id INTEGER, event_type TEXT, actor_type TEXT, actor_id INTEGER, old_value TEXT, new_value TEXT, created_at TEXT',
            'tl_issue_comment' => 'id INTEGER PRIMARY KEY, tstamp INTEGER, issue_id INTEGER, visibility TEXT, author_type TEXT, author_id INTEGER, body TEXT, created_at TEXT',
            'tl_issue_notification' => 'id INTEGER PRIMARY KEY, event_uuid BLOB, issue_id INTEGER, recipient TEXT, template_key TEXT, status TEXT, attempt_count INTEGER, created_at TEXT',
            'tl_issue_service' => 'id INTEGER PRIMARY KEY, notification_recipients TEXT',
            'tl_issue_profile' => 'id INTEGER PRIMARY KEY, reopen_roles TEXT, service_scoped_permissions TEXT, mail_recipients TEXT',
            'tl_user' => 'id INTEGER PRIMARY KEY, email TEXT',
            'tl_member' => 'id INTEGER PRIMARY KEY, email TEXT',
            'tl_user_group' => 'id INTEGER PRIMARY KEY, issue_workflow_role TEXT, issue_workflow_services TEXT, disable TEXT, start TEXT, stop TEXT',
            'tl_issue_service_group' => 'user_group_id INTEGER, service_id INTEGER, role_key TEXT',
        ] as $table => $columns) $this->real->executeStatement('CREATE TABLE '.$table.' ('.$columns.')');
        $this->real->insert('tl_issue_profile', ['id' => 1, 'reopen_roles' => serialize(['manager']), 'service_scoped_permissions' => '1', 'mail_recipients' => 'notify@example.com']);
        $this->real->insert('tl_issue_service', ['id' => 1, 'notification_recipients' => '[]']);
        $this->real->insert('tl_issue', ['id' => 1, 'status_id' => 1, 'service_id' => 1, 'profile_id' => 1, 'member_id' => 7, 'version' => 1, 'ticket_number' => 'TEST-1', 'title' => 'Test']);
        foreach ([[1, 'Neu', 0], [2, 'Gelöst', 1]] as [$id, $title, $resolved]) $this->real->insert('tl_issue_status', ['id' => $id, 'title' => $title, 'is_resolved' => $resolved]);
        $this->real->insert('tl_issue_transition', ['from_status_id' => 1, 'to_status_id' => 2, 'role_key' => 'agent', 'require_public_comment' => 1]);
        $this->real->insert('tl_user_group', ['id' => 3, 'issue_workflow_role' => 'agent', 'issue_workflow_services' => serialize([1]), 'disable' => '', 'start' => '', 'stop' => '']);
        // Execute real SQL in SQLite; only MariaDB's row-lock suffix is removed.
        $this->db = $this->createStub(Connection::class);
        foreach (['fetchAssociative', 'fetchAllAssociative', 'fetchOne', 'fetchFirstColumn', 'fetchAllKeyValue', 'executeStatement'] as $method) {
            $this->db->method($method)->willReturnCallback(fn ($sql, $params = []) => $this->real->$method(str_replace(' FOR UPDATE', '', $sql), $params));
        }
        foreach (['insert', 'update'] as $method) $this->db->method($method)->willReturnCallback(fn (...$args) => $this->real->$method(...$args));
        $this->db->method('lastInsertId')->willReturnCallback(fn () => $this->real->lastInsertId());
        $this->db->method('transactional')->willReturnCallback(fn ($callback) => $this->real->transactional($callback));
        $settings = new SettingsService(new SettingsRepository($this->db));
        $this->tokens = new TokenStorage();
        $this->backend(false);
        $this->workflow = new IssueWorkflowService(new IssueRepository($this->db), new NotificationService($this->db, $settings, $this->createStub(MailerInterface::class), 'test@example.com'), new WorkflowRoles($this->db, $settings), $settings, $this->tokens);
    }

    private function backend(bool $admin): void
    {
        $user = $this->createStub(BackendUser::class);
        $user->method('__get')->willReturnMap([['id', 9], ['isAdmin', $admin], ['groups', [3]]]);
        $user->method('hasAccess')->willReturn(true);
        $this->tokens->setToken(new UsernamePasswordToken($user, 'contao_backend', []));
    }

    private function rejected(callable $change): void
    {
        try { $change(); self::fail('Transition should be rejected'); }
        catch (\DomainException) {
            self::assertSame(1, (int) $this->real->fetchOne('SELECT status_id FROM tl_issue WHERE id=1'));
            self::assertSame(0, (int) $this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_history'));
        }
    }

    public function testRequiredPublicCommentAndSingleHistoryEntry(): void
    {
        $this->rejected(fn () => $this->workflow->transition(1, 2));
        $this->workflow->transition(1, 2, 'Gelöst', 1);
        self::assertSame(2, (int) $this->real->fetchOne('SELECT status_id FROM tl_issue WHERE id=1'));
        self::assertSame(1, (int) $this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_history'));
        self::assertSame(1, (int) $this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_notification'));
        self::assertSame('public', $this->real->fetchOne('SELECT visibility FROM tl_issue_comment'));
        self::assertNotNull($this->real->fetchOne('SELECT resolved_at FROM tl_issue'));
    }

    public function testServiceScopeDisabledGroupsAndDisabledRules(): void
    {
        foreach ([['issue_workflow_services' => serialize([2])], ['issue_workflow_services' => serialize([1]), 'disable' => '1'], ['disable' => '', 'stop' => (string) (time() - 1)]] as $changes) {
            $this->real->update('tl_user_group', $changes, ['id' => 3]);
            $this->rejected(fn () => $this->workflow->transition(1, 2, 'Antwort'));
        }
        $this->backend(true);
        $this->real->update('tl_issue_transition', ['published' => 0], ['from_status_id' => 1]);
        $this->rejected(fn () => $this->workflow->transition(1, 2, 'Antwort'));
    }

    public function testForeignMemberAndStaleStatusCannotChangeTicket(): void
    {
        $member = $this->createStub(FrontendUser::class);
        $member->method('__get')->willReturnMap([['id', 8]]);
        $this->tokens->setToken(new UsernamePasswordToken($member, 'contao_frontend', []));
        $this->real->update('tl_issue_transition', ['role_key' => 'member'], ['from_status_id' => 1]);
        $this->rejected(fn () => $this->workflow->transition(1, 2, 'Antwort'));
        $this->backend(true);
        $this->rejected(fn () => $this->workflow->transition(1, 2, 'Antwort', 99));
    }

    public function testManagerDoesNotAutomaticallyInheritAgentAndMemberCanUseOwnRule(): void
    {
        $this->real->update('tl_user_group', ['issue_workflow_role' => 'manager'], ['id' => 3]);
        $this->rejected(fn () => $this->workflow->transition(1, 2, 'Antwort'));
        $member = $this->createStub(FrontendUser::class);
        $member->method('__get')->willReturnMap([['id', 7]]);
        $this->tokens->setToken(new UsernamePasswordToken($member, 'contao_frontend', []));
        $this->real->update('tl_issue_transition', ['role_key' => 'member'], ['from_status_id' => 1]);
        $this->workflow->transition(1, 2, 'Erledigt', 1);
        self::assertSame('member', $this->real->fetchOne('SELECT actor_type FROM tl_issue_history'));
    }

    public function testInactiveTargetAndVersionRestoreAreRejected(): void
    {
        $this->real->update('tl_issue_status', ['published' => 0], ['id' => 2]);
        $this->rejected(fn () => $this->workflow->transition(1, 2, 'Antwort'));
        $requests = new RequestStack();
        $requests->push(Request::create('/', 'POST', ['FORM_SUBMIT' => 'tl_version']));
        $callback = new IssueWorkflowCallbacks($this->db, $this->workflow, $requests);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
        $callback->preventVersionRestore();
    }

    public function testReopenRequiresBothProfileRoleAndRule(): void
    {
        $this->real->update('tl_issue', ['status_id' => 2], ['id' => 1]);
        $this->real->insert('tl_issue_transition', ['from_status_id' => 2, 'to_status_id' => 1, 'role_key' => 'agent']);
        $issue = $this->real->fetchAssociative('SELECT * FROM tl_issue WHERE id=1');
        self::assertSame([], $this->workflow->choices($issue));
        $this->backend(true);
        self::assertSame([], $this->workflow->choices($issue));
        $this->real->insert('tl_issue_transition', ['from_status_id' => 2, 'to_status_id' => 1, 'role_key' => 'manager']);
        $this->workflow->transition(1, 1, null, 2);
        self::assertSame(1, (int) $this->real->fetchOne('SELECT status_id FROM tl_issue'));
        self::assertNull($this->real->fetchOne('SELECT resolved_at FROM tl_issue'));
    }

    public function testBackendDelaysStatusUntilSuccessfulSubmit(): void
    {
        $requests = new RequestStack();
        $request = Request::create('/', 'POST', ['FORM_SUBMIT' => 'tl_issue', 'journal_body' => 'Antwort', 'journal_visibility' => 'internal']);
        $requests->push($request);
        $callback = new IssueWorkflowCallbacks($this->db, $this->workflow, $requests);
        $dc = $this->createStub(DataContainer::class);
        $dc->method('__get')->willReturnMap([['id', 1]]);
        $this->rejected(fn () => $callback->validateStatus(2, $dc));
        $request->request->set('journal_visibility', 'public');
        self::assertSame(1, $callback->validateStatus(2, $dc));
        self::assertSame(1, (int) $this->real->fetchOne('SELECT status_id FROM tl_issue'));
        $callback->commit($dc);
        $callback->commit($dc);
        self::assertSame(2, (int) $this->real->fetchOne('SELECT status_id FROM tl_issue'));
        self::assertSame(1, (int) $this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_history'));
        self::assertFalse($request->request->has('journal_body'));
    }
}
