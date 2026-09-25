<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage;

final class AttachmentDirectoryCallback
{
    public function validateFolder(?string $value, ?\Contao\DataContainer $dc = null): ?string
    {
        if (!$value && $dc?->table === 'tl_issue_profile' && \Contao\Input::post('attachment_storage') === 'files') {
            throw new \InvalidArgumentException('Bitte für den Speicherort files einen Ordner auswählen.');
        }
        if ($value) {
            LocalAttachmentStorage::resolveFolder(\Contao\StringUtil::binToUuid($value));
        }
        return $value;
    }

    public function validate(string $value): string
    {
        $value = trim($value);
        LocalAttachmentStorage::validateDirectory($value);
        return $value;
    }
}
