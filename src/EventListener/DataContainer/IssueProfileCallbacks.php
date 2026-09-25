<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Diversworld\ContaoIssueServiceBundle\Application\TicketPattern;

final class IssueProfileCallbacks
{
    #[AsCallback(table: 'tl_issue_profile', target: 'fields.ticket_pattern.save')]
    public function ticketPattern(string $value): string
    {
        if (!str_contains($value, '{SEQ}')) {
            throw new \InvalidArgumentException('Das Ticketnummer-Muster muss {SEQ} enthalten.');
        }
        (new TicketPattern())->render($value, 'SERVICE', (int) date('Y'), 1);
        return $value;
    }

    #[AsCallback(table: 'tl_issue_profile', target: 'fields.mail_recipients.save')]
    public function recipients(?string $value): string
    {
        $emails = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R+/', $value ?? '') ?: []))));
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Bitte pro Zeile eine gültige E-Mail-Adresse eingeben.');
            }
        }
        return implode("\n", $emails);
    }

    #[AsCallback(table: 'tl_issue_profile', target: 'fields.max_file_size.save')]
    #[AsCallback(table: 'tl_issue_profile', target: 'fields.max_files_per_issue.save')]
    #[AsCallback(table: 'tl_issue_profile', target: 'fields.retention_days.save')]
    public function positive(mixed $value): int
    {
        if ((int) $value < 1) {
            throw new \InvalidArgumentException('Bitte einen Wert größer als 0 eingeben.');
        }
        return (int) $value;
    }
}
