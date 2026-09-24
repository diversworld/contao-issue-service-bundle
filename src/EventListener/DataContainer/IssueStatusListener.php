<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class IssueStatusListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection $db,
        private readonly Slug       $slug,
    )
    {
    }

    #[AsCallback(table: 'tl_issue_status', target: 'fields.status_key.save')]
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_issue_status', 'title', 'status_key');
    }

    #[AsCallback(table: 'tl_issue_status', target: 'config.oncopy')]
    public function onCopy(int|string $insertId, DataContainer $dc): void
    {
        $this->updateAliasAfterCopy($this->db, $this->slug, $insertId, 'tl_issue_status', 'title', 'status_key');
    }
}