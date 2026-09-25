<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoIssueServiceBundle\Application\IssueWorkflowService;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class IssueWorkflowCallbacks
{
    private array $pending = [];

    public function __construct(private readonly Connection $db, private readonly IssueWorkflowService $workflow, private readonly RequestStack $requests) {}

    private function issue(DataContainer $dc): array
    {
        return $this->db->fetchAssociative('SELECT * FROM tl_issue WHERE id=:id', ['id' => $dc->id]) ?: [];
    }

    private function publicComment(): ?string
    {
        $request = $this->requests->getCurrentRequest();
        return $request?->request->get('journal_visibility') === 'public' ? (string) $request->request->get('journal_body', '') : null;
    }

    #[AsCallback(table: 'tl_issue', target: 'config.onload', priority: 200)]
    public function preventVersionRestore(): void
    {
        if ($this->requests->getCurrentRequest()?->request->get('FORM_SUBMIT') === 'tl_version') {
            throw new AccessDeniedHttpException('Ticket-Versionen können nicht direkt wiederhergestellt werden. Bitte Änderungen über die Ticketbearbeitung und die Workflow-Regeln vornehmen.');
        }
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.status_id.options')]
    public function options(?DataContainer $dc = null): array
    {
        if (!$dc || !$dc->id) return [];
        $issue = $this->issue($dc);
        if (!$issue || !(int) $issue['status_id']) {
            return $this->db->fetchAllKeyValue("SELECT id,title FROM tl_issue_status WHERE status_key='new' AND published=1");
        }
        $current = $this->db->fetchAllKeyValue('SELECT id,title FROM tl_issue_status WHERE id=:id', ['id' => $issue['status_id']]);
        return $current + $this->workflow->choices($issue);
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.status_id.load')]
    public function initialStatus(mixed $value): mixed
    {
        return $value ?: $this->db->fetchOne("SELECT id FROM tl_issue_status WHERE status_key='new' AND published=1");
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.service_id.save')]
    public function validateService(mixed $value, DataContainer $dc): mixed
    {
        $issue = $this->issue($dc);
        if (!$issue || (int) $issue['service_id'] === (int) $value) return $value;
        if ((int) $issue['service_id'] && !$this->workflow->roles($issue)) {
            throw new \DomainException('Keine Workflow-Rolle für den bisherigen Service.');
        }
        $issue['service_id'] = (int) $value;
        if (!$this->workflow->roles($issue)) throw new \DomainException('Keine Workflow-Rolle für den ausgewählten Service.');
        return $value;
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.status_id.save')]
    public function validateStatus(mixed $value, DataContainer $dc): int
    {
        $issue = $this->issue($dc);
        if (!$issue) throw new \DomainException('Ticket nicht verfügbar.');
        $old = (int) $issue['status_id'];
        $target = (int) $value;
        unset($this->pending[(int) $dc->id]);
        if ($old === $target) return $old;
        if (!$old) {
            $initial = (int) $this->db->fetchOne("SELECT id FROM tl_issue_status WHERE status_key='new' AND published=1");
            if (!$initial || $initial !== $target) throw new \DomainException('Neue Tickets müssen den Status Neu erhalten.');
            return $initial;
        }
        $this->workflow->assertAllowed($issue, $target, $this->publicComment());
        $request = $this->requests->getCurrentRequest();
        if ($request?->request->has('service_id') && (int) $request->request->get('service_id') !== (int) $issue['service_id']) {
            $issue['service_id'] = (int) $request->request->get('service_id');
            $this->workflow->assertAllowed($issue, $target, $this->publicComment());
        }
        $this->pending[(int) $dc->id] = [$old, $target, $this->publicComment()];
        // DC_Table must not write the status before all other widgets are valid.
        return $old;
    }

    #[AsCallback(table: 'tl_issue', target: 'config.onsubmit', priority: 100)]
    public function commit(DataContainer $dc): void
    {
        $id = (int) $dc->id;
        if (!isset($this->pending[$id])) return;
        [$old, $target, $comment] = $this->pending[$id];
        unset($this->pending[$id]);
        try {
            $this->workflow->transition($id, $target, $comment, $old);
            if ($comment !== null && trim($comment) !== '') {
                $this->requests->getCurrentRequest()?->request->remove('journal_body');
            }
        } catch (\DomainException $error) {
            \Contao\Message::addError($error->getMessage());
            $this->requests->getCurrentRequest()?->request->remove('journal_body');
        }
    }
}
