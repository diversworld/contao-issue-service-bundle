<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

final class IssueTransitionCallbacks
{
    public function __construct(private readonly Connection $db, private readonly RequestStack $requests) {}

    #[AsCallback(table: 'tl_issue_transition', target: 'fields.from_status_id.save')]
    #[AsCallback(table: 'tl_issue_transition', target: 'fields.to_status_id.save')]
    #[AsCallback(table: 'tl_issue_transition', target: 'fields.role_key.save')]
    public function validate(mixed $value, DataContainer $dc): mixed
    {
        $request = $this->requests->getCurrentRequest();
        if ($request?->request->get('FORM_SUBMIT') !== 'tl_issue_transition') return $value;

        $from = (int) $request->request->get('from_status_id', 0);
        $to = (int) $request->request->get('to_status_id', 0);
        $role = (string) $request->request->get('role_key', '');
        if (!$from || !$to || !in_array($role, ['member', 'agent', 'manager'], true)) return $value;
        if ($from === $to) {
            throw new \DomainException($GLOBALS['TL_LANG']['tl_issue_transition']['sameStatus']);
        }
        if ($this->db->fetchOne(
            'SELECT id FROM tl_issue_transition WHERE from_status_id=? AND to_status_id=? AND role_key=? AND id<>?',
            [$from, $to, $role, (int) $dc->id],
        )) {
            throw new \DomainException($GLOBALS['TL_LANG']['tl_issue_transition']['duplicateRule']);
        }
        return $value;
    }
}
