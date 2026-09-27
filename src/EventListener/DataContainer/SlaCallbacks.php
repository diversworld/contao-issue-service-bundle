<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\{BackendUser, DataContainer};
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\{BusinessCalendar, SlaCalculationService, SlaEscalationService};
use Diversworld\ContaoIssueServiceBundle\Application\License\{PremiumFeatureResolver, LicenseService};
use Diversworld\ContaoIssueServiceBundle\Security\WorkflowRoles;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SlaCallbacks
{
    public function __construct(private readonly Connection $db, private readonly PremiumFeatureResolver $premium,
        private readonly SlaCalculationService $sla, private readonly LicenseService $licenses,
        private readonly BusinessCalendar $calendar, private readonly WorkflowRoles $roles,
        private readonly \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaHistory $history) {}

    /** @var array<string, array<string, mixed>> */
    private array $before = [];


    #[AsCallback(table: 'tl_issue_license', target: 'config.onload')]
    #[AsCallback(table: 'tl_issue_license_validation', target: 'config.onload')]
    public function licenseAccess(): void
    {
        $user = BackendUser::getInstance();
        if (!$user instanceof BackendUser || !$user->isAdmin) throw new AccessDeniedHttpException('Lizenzverwaltung ist Administratoren vorbehalten.');
    }

    #[AsCallback(table: 'tl_issue_sla', target: 'config.onload')]
    #[AsCallback(table: 'tl_issue_sla_level', target: 'config.onload')]
    #[AsCallback(table: 'tl_issue_sla_escalation', target: 'config.onload')]
    #[AsCallback(table: 'tl_issue_sla_history', target: 'config.onload')]
    public function administration(): void
    {
        $this->licenseAccess();
        $this->premium->requireSla();
    }

    #[AsCallback(table: 'tl_issue_license', target: 'fields.token.save')]
    public function token(mixed $value): string
    {
        $this->licenseAccess();
        $this->licenses->import((string) $value);
        return trim((string) $value);
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.sla_override_id.save')]
    #[AsCallback(table: 'tl_issue_service', target: 'fields.sla_id.save')]
    public function assignment(mixed $value, DataContainer $dc): int
    {
        $table = $dc->table === 'tl_issue_service' ? 'tl_issue_service' : 'tl_issue';
        $field = $table === 'tl_issue' ? 'sla_override_id' : 'sla_id';
        $record = $this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=?', [$dc->id]);
        if ($record && (int) $record[$field] === (int) $value) return (int) $value;
        $this->premium->requireSla();
        $user = BackendUser::getInstance();
        if (!$user instanceof BackendUser) throw new AccessDeniedHttpException();
        if (!$user->isAdmin && ($table !== 'tl_issue' || !$record || !in_array('manager', $this->roles->forUser($user, $record), true))) throw new AccessDeniedHttpException('SLA-Zuordnung erfordert eine Managerrolle für diesen Service.');
        if ((int) $value && !$this->db->fetchOne('SELECT id FROM tl_issue_sla WHERE id=? AND published=1', [(int) $value])) throw new \DomainException('Bitte eine aktive SLA-Definition auswählen.');
        return (int) $value;
    }

    #[AsCallback(table: 'tl_issue', target: 'config.onsubmit', priority: -100)]
    public function synchronize(DataContainer $dc): void
    {
        if ($dc->id) $this->sla->synchronize((int) $dc->id, null, (int) BackendUser::getInstance()->id);
    }

    #[AsCallback(table: 'tl_issue_sla', target: 'fields.timezone.save')]
    public function timezone(mixed $value): string
    {
        new \DateTimeZone((string) $value);
        return (string) $value;
    }
    #[AsCallback(table: 'tl_issue_sla', target: 'fields.business_hours.save')]
    public function hours(mixed $value): string
    {
        $hours = json_decode((string) $value, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($hours)) throw new \DomainException('Geschäftszeiten müssen ein JSON-Objekt sein.');
        $this->calendar->validate('UTC', $hours, []);
        return (string) $value;
    }
    #[AsCallback(table: 'tl_issue_sla', target: 'fields.holidays.save')]
    public function holidays(mixed $value): string
    {
        $holidays = json_decode(trim((string) $value) ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($holidays) || !array_is_list($holidays)) throw new \DomainException('Feiertage müssen eine JSON-Liste sein.');
        $this->calendar->validate('UTC', [1 => [['09:00','17:00']]], $holidays);
        return json_encode($holidays, JSON_THROW_ON_ERROR);
    }
    #[AsCallback(table: 'tl_issue_sla', target: 'fields.response_minutes.save')]
    #[AsCallback(table: 'tl_issue_sla', target: 'fields.resolve_minutes.save')]
    public function duration(mixed $value): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1 || (int) $value > 525600) throw new \DomainException('SLA-Dauer muss zwischen 1 und 525600 Minuten liegen.');
        return (int) $value;
    }
    #[AsCallback(table: 'tl_issue_sla_escalation', target: 'fields.recipients.save')]
    public function recipients(mixed $value): string
    {
        $lines = preg_split('/[\r\n,;]+/', trim((string) $value)) ?: [];
        foreach ($lines as $line) if (!filter_var(trim($line), FILTER_VALIDATE_EMAIL)) throw new \DomainException('Ungültige Empfängeradresse.');
        if (!$lines) throw new \DomainException('Mindestens ein Empfänger erforderlich.');
        return implode("\n", SlaEscalationService::recipients((string) $value));
    }
    #[AsCallback(table: 'tl_issue_sla', target: 'config.onload', priority: -100)]
    #[AsCallback(table: 'tl_issue_sla_level', target: 'config.onload', priority: -100)]
    #[AsCallback(table: 'tl_issue_sla_escalation', target: 'config.onload', priority: -100)]
    #[AsCallback(table: 'tl_issue_service', target: 'config.onload', priority: -100)]
    #[AsCallback(table: 'tl_issue', target: 'config.onload', priority: -100)]
    public function remember(DataContainer $dc): void
    {
        $table = (string) $dc->table;
        if (!$dc->id || !in_array($table, ['tl_issue_sla','tl_issue_sla_level','tl_issue_sla_escalation','tl_issue_service','tl_issue'], true)) return;
        $this->before[$table.':'.$dc->id] = $this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=?', [$dc->id]) ?: [];
    }

    #[AsCallback(table: 'tl_issue_sla', target: 'config.onsubmit', priority: -200)]
    #[AsCallback(table: 'tl_issue_sla_level', target: 'config.onsubmit', priority: -200)]
    #[AsCallback(table: 'tl_issue_sla_escalation', target: 'config.onsubmit', priority: -200)]
    #[AsCallback(table: 'tl_issue_service', target: 'config.onsubmit', priority: -200)]
    #[AsCallback(table: 'tl_issue', target: 'config.onsubmit', priority: -200)]
    public function auditConfiguration(DataContainer $dc): void
    {
        if (!$this->premium->enabled()) return;
        $table = (string) $dc->table;
        if (!$dc->id || !in_array($table, ['tl_issue_sla','tl_issue_sla_level','tl_issue_sla_escalation','tl_issue_service','tl_issue'], true)) return;
        $before = $this->before[$table.':'.$dc->id] ?? [];
        $after = $this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=?', [$dc->id]) ?: [];
        if ($table === 'tl_issue' || $table === 'tl_issue_service') {
            $fields = $table === 'tl_issue' ? ['sla_override_id'=>true] : ['sla_id'=>true];
            $before = array_intersect_key($before, $fields);
            $after = array_intersect_key($after, $fields);
        }
        unset($before['tstamp'], $after['tstamp']);
        if ($before === $after) return;
        $this->db->transactional(function () use ($table, $dc, $before, $after): void {
            // A single lock serializes the global configuration audit chain.
            $this->db->fetchOne('SELECT id FROM tl_issue_license WHERE id=1 FOR UPDATE');
            $this->history->append(0, 'configuration_changed', ['table'=>$table,'id'=>(int)$dc->id,'before'=>$before,'after'=>$after], time(), (int)BackendUser::getInstance()->id);
        });
        $this->before[$table.':'.$dc->id] = $after;
    }

    public function licenseStatus(): string
    {
        $this->licenseAccess();
        $status = $this->licenses->status();
        $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<div class="tl_box"><p>Lizenzstatus: '.$escape($status['state']).'</p><p>Gültig bis: '
            .$escape(isset($status['claims']['expires_at']) ? date('d.m.Y H:i', $status['claims']['expires_at']) : '–').'</p></div>';
    }

    public function display(DataContainer $dc): string
    {
        $view = $this->sla->view((int) $dc->id);
        if (!$view) return '<p class="tl_info">Kein aktives SLA / keine gültige SLA-Lizenz.</p>';
        $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<div class="tl_box"><h3>SLA</h3><p>'.$escape($view['state']).($view['breached'] ? ' · SLA verletzt' : '').'</p>';
        foreach (['response_due_at' => 'Reaktion bis', 'resolve_due_at' => 'Lösung bis'] as $field => $title) $html .= '<p>'.$title.': '.$escape(date('d.m.Y H:i', (int) $view[$field])).'</p>';
        foreach ($view['history'] as $entry) $html .= '<p>'.$escape(date('d.m.Y H:i', (int) $entry['created_at'])).' · '.$escape($entry['event_type']).'</p>';
        return $html.'</div>';
    }
}
