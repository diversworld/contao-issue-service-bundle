<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class IssueListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection $db,
        private readonly Slug       $slug,
    )
    {
    }

    // Tickets have no alias column; do not register this legacy helper as a DCA callback.
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_issue');
    }
}
