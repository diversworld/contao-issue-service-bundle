<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage;

final class AttachmentDirectoryCallback
{
    public function validateFolder(?string $value): ?string
    {
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
