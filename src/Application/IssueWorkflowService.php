<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;

use Contao\BackendUser;
use Contao\FrontendUser;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Diversworld\ContaoIssueServiceBundle\Security\WorkflowRoles;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class IssueWorkflowService
{
    public function __construct(
        private readonly IssueRepository $issues,
        private readonly NotificationService $notifications,
        private readonly WorkflowRoles $roles,
        private readonly SettingsService $settings,
        private readonly TokenStorageInterface $tokens,
    ) {}

    public function roles(array $issue): array
    {
        return $this->roles->forUser($this->tokens->getToken()?->getUser(), $issue);
    }

    /** @return list<array<string, mixed>> */
    private function rules(array $issue): array
    {
        $db = $this->issues->connection();
        $source = $db->fetchAssociative('SELECT is_closed, is_resolved FROM tl_issue_status WHERE id=:id', ['id' => $issue['status_id']]);
        if (!$source || !empty($issue['deleted_at'])) return [];
        $roles = $this->roles($issue);
        $reopen = $this->settings->forProfile((int) ($issue['profile_id'] ?? 0))->json('reopen_roles', ['member', 'agent', 'manager']);
        return array_values(array_filter($db->fetchAllAssociative(
            'SELECT t.to_status_id, t.role_key, t.require_public_comment, s.title, s.is_closed, s.is_resolved FROM tl_issue_transition t JOIN tl_issue_status s ON s.id=t.to_status_id WHERE t.from_status_id=:id AND t.to_status_id<>t.from_status_id AND t.published=1 AND s.published=1 ORDER BY s.sort_order, s.id',
            ['id' => $issue['status_id']],
        ), static function (array $rule) use ($roles, $source, $reopen): bool {
            $reopening = ($source['is_closed'] || $source['is_resolved']) && !$rule['is_closed'] && !$rule['is_resolved'];
            return in_array($rule['role_key'], $roles, true) && (!$reopening || in_array($rule['role_key'], $reopen, true));
        }));
    }

    public function choices(array $issue): array
    {
        $choices = [];
        foreach ($this->rules($issue) as $rule) $choices[(int) $rule['to_status_id']] = $rule['title'];
        return $choices;
    }

    public function assertAllowed(array $issue, int $target, ?string $publicComment): void
    {
        $matching = array_filter($this->rules($issue), static fn (array $rule): bool => (int) $rule['to_status_id'] === $target);
        if (!$matching) throw new \DomainException('Für diesen Statuswechsel ist keine aktive Workflow-Regel für Ihre Rolle vorhanden.');
        foreach ($matching as $rule) {
            if (!$rule['require_public_comment'] || trim((string) $publicComment) !== '') return;
        }
        throw new \DomainException('Dieser Statuswechsel erfordert eine öffentliche Antwort.');
    }

    public function transition(int $issueId, int $target, ?string $publicComment = null, ?int $expectedStatus = null): ?int
    {
        $db = $this->issues->connection();
        return $db->transactional(function () use ($db, $issueId, $target, $publicComment, $expectedStatus): ?int {
            $issue = $db->fetchAssociative('SELECT * FROM tl_issue WHERE id=:id FOR UPDATE', ['id' => $issueId]);
            if (!$issue || !empty($issue['deleted_at'])) throw new \DomainException('Ticket nicht verfügbar.');
            if ($expectedStatus !== null && (int) $issue['status_id'] !== $expectedStatus) throw new \DomainException('Der Ticketstatus wurde zwischenzeitlich geändert. Bitte neu laden.');
            if ((int) $issue['status_id'] === $target) return null;
            $this->assertAllowed($issue, $target, $publicComment);
            $user = $this->tokens->getToken()?->getUser();
            if (!$user instanceof BackendUser && !$user instanceof FrontendUser) throw new \DomainException('Anmeldung erforderlich.');
            $actor = $user instanceof BackendUser ? 'user' : 'member';
            $now = date('Y-m-d H:i:s');
            $status = $db->fetchAssociative('SELECT is_resolved, is_closed FROM tl_issue_status WHERE id=:id', ['id' => $target]);
            $db->update('tl_issue', [
                'status_id' => $target, 'updated_at' => $now, 'last_public_activity_at' => $now, 'tstamp' => time(), 'version' => (int) $issue['version'] + 1,
                'resolved_at' => $status['is_resolved'] ? ($issue['resolved_at'] ?: $now) : null,
                'closed_at' => $status['is_closed'] ? ($issue['closed_at'] ?: $now) : null,
            ], ['id' => $issueId]);
            $commentId = null;
            if (trim((string) $publicComment) !== '') {
                $db->insert('tl_issue_comment', ['tstamp' => time(), 'issue_id' => $issueId, 'visibility' => 'public', 'author_type' => $actor, 'author_id' => (int) $user->id, 'body' => trim($publicComment), 'created_at' => $now]);
                $commentId = (int) $db->lastInsertId();
            }
            $db->insert('tl_issue_history', ['issue_id' => $issueId, 'event_type' => 'status_changed', 'actor_type' => $actor, 'actor_id' => (int) $user->id,
                'old_value' => json_encode(['status_id' => (int) $issue['status_id']], JSON_THROW_ON_ERROR), 'new_value' => json_encode(['status_id' => $target], JSON_THROW_ON_ERROR), 'created_at' => $now]);
            $this->notifications->enqueue($issueId, 'status_changed');
            return $commentId;
        });
    }
}
