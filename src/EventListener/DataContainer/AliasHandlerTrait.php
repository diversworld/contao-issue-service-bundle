<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

trait AliasHandlerTrait
{
    protected function generateAliasWithValidation(
        Connection    $db,
        Slug          $slug,
        mixed         $varValue,
        DataContainer $dc,
        string        $table,
        string        $titleField = 'title'
    ): mixed
    {
        $aliasExists = function (string $alias) use ($db, $dc, $table): bool {
            return (bool)$db->fetchOne(
                "SELECT id FROM $table WHERE alias=? AND id!=? LIMIT 1",
                [$alias, $dc->id]
            );
        };

        if (!$varValue) {
            $varValue = $slug->generate(
                $dc->activeRecord->{$titleField},
                [],
                $aliasExists
            );
        } elseif (preg_match('/^[1-9]\d*$/', (string)$varValue)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'] ?? 'Alias %s must not be numeric!', $varValue));
        } elseif ($aliasExists((string)$varValue)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'Alias %s already exists!', $varValue));
        }

        return $varValue;
    }
}
