<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoIssueServiceBundle\Application\CommentService;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

final class IssueJournalCallbacks
{
    private array $before = [];

    public function __construct(
        private readonly Connection $db,
        private readonly IssueRepository $issues,
        private readonly CommentService $comments,
        private readonly RequestStack $requests,
        private readonly \Symfony\Component\Routing\Generator\UrlGeneratorInterface $router,
    ) {
    }

    #[AsCallback(table: 'tl_issue', target: 'config.onload')]
    public function remember(DataContainer $dc): void
    {
        if ($dc->id) {
            $this->before[(int) $dc->id] = $this->db->fetchAssociative('SELECT status_id, priority, assigned_user_id, resolution FROM tl_issue WHERE id=:id', ['id' => $dc->id]);
        }
    }

    public function render(DataContainer $dc): string
    {
        $escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<div class="clr"><h3>Journal und Antworten</h3></div>';
        $html .= '<div class="tl_box"><h3>Anhänge</h3><ul>';
        foreach ($this->issues->attachments((int) $dc->id, true) as $attachment) {
            $url = $this->router->generate('issue_service_backend_attachment', ['id' => $attachment['id']]);
            $preview = $this->router->generate('issue_service_backend_attachment', ['id' => $attachment['id'], 'preview' => 1]);
            $html .= '<li><a href="'.$escape($url).'">'.$escape($attachment['original_name']).'</a> · <a href="'.$escape($preview).'" target="_blank" rel="noopener noreferrer">Anzeigen</a> ('.round($attachment['file_size'] / 1024, 1).' KB)</li>';
        }
        $html .= '</ul></div>';
        $authors = ['member' => 'Mitglied', 'guest' => 'Gast', 'user' => 'Bearbeiter', 'system' => 'System'];
        foreach ($this->issues->timeline((int) $dc->id, true) as $entry) {
            $visibility = $entry['visibility'] === 'internal' ? 'Intern' : 'Für Frontend-Nutzer sichtbar';
            $html .= '<div class="tl_box"><p><strong>'.$escape($visibility).'</strong> · '.$escape($authors[$entry['author_type']] ?? 'System').' · '.$escape($entry['created_at']).'</p><p>'.nl2br($escape($entry['text'])).'</p></div>';
        }
        $request = $this->requests->getCurrentRequest();
        $body = (string) ($request?->request->get('journal_body', '') ?? '');
        $public = $request?->request->get('journal_visibility') === 'public';
        return '<div class="widget clr"><h3><label for="journal_body">Neue Notiz / Antwort</label></h3><textarea id="journal_body" name="journal_body" class="tl_textarea" rows="6">'.$escape($body).'</textarea></div>'
            .'<div class="widget clr"><h3><label for="journal_visibility">Sichtbarkeit der neuen Notiz</label></h3><select id="journal_visibility" name="journal_visibility" class="tl_select"><option value="internal"'.(!$public ? ' selected' : '').'>Nur intern im Backend</option><option value="public"'.($public ? ' selected' : '').'>Im Frontend-Verlauf sichtbar</option></select><p class="tl_help tl_tip">Die Notiz wird beim Speichern des Tickets dem Journal hinzugefügt.</p></div>'.$html;
    }

    #[AsCallback(table: 'tl_issue', target: 'config.onsubmit')]
    public function save(DataContainer $dc): void
    {
        $id = (int) $dc->id;
        $request = $this->requests->getCurrentRequest();
        if (!$id || !$request || $request->request->get('FORM_SUBMIT') !== 'tl_issue') {
            return;
        }
        $userId = (int) BackendUser::getInstance()->id;
        $this->db->transactional(function () use ($id, $request, $userId): void {
            $now = date('Y-m-d H:i:s');
            $after = $this->db->fetchAssociative('SELECT status_id, priority, assigned_user_id, resolution FROM tl_issue WHERE id=:id', ['id' => $id]);
            $before = $this->before[$id] ?? false;
            $publicChange = false;
            if ($before && $after) {
                foreach (['priority' => 'priority_changed', 'assigned_user_id' => 'assignment_changed', 'resolution' => 'resolution_changed'] as $field => $event) {
                    if ((string) $before[$field] === (string) $after[$field]) {
                        continue;
                    }
                    $this->db->insert('tl_issue_history', [
                        'issue_id' => $id, 'event_type' => $event, 'actor_type' => 'user', 'actor_id' => $userId,
                        'old_value' => json_encode([$field => $before[$field]], JSON_THROW_ON_ERROR),
                        'new_value' => json_encode([$field => $after[$field]], JSON_THROW_ON_ERROR), 'created_at' => $now,
                    ]);
                    $publicChange = $publicChange || in_array($field, ['status_id', 'priority'], true);
                }

            }
            if ($publicChange) {
                $this->db->update('tl_issue', ['last_public_activity_at' => $now], ['id' => $id]);
            }
            $body = trim((string) $request->request->get('journal_body', ''));
            if ($body !== '') {
                if ($request->request->get('journal_visibility') === 'public') {
                    $this->comments->addPublic($id, 'user', $userId, $body);
                } else {
                    $this->comments->addInternal($id, $userId, $body);
                }
            }
            $this->before[$id] = $after;
            $request->request->remove('journal_body');
        });
    }
}
