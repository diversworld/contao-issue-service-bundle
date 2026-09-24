<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;

trait AliasHandlerTrait
{
    /** @param list<string> $scopeFields */
    protected function generateAliasWithValidation(
        Connection    $db,
        Slug          $slug,
        mixed         $varValue,
        DataContainer $dc,
        string        $table,
        string        $titleField = 'title',
        string        $aliasField = 'alias',
        array         $scopeFields = []
    ): mixed
    {
        $activeRecord = $dc instanceof DC_Table ? $dc->getActiveRecord() : $dc->getCurrentRecord();

        $aliasExists = function (string $alias) use ($db, $dc, $table, $aliasField, $scopeFields, $activeRecord): bool {
            $conditions = ["$aliasField=?", 'id!=?'];
            $parameters = [$alias, $dc->id];

            foreach ($scopeFields as $scopeField) {
                $conditions[] = "$scopeField=?";
                $parameters[] = $activeRecord[$scopeField] ?? null;
            }

            return (bool)$db->fetchOne(
                sprintf('SELECT id FROM %s WHERE %s LIMIT 1', $table, implode(' AND ', $conditions)),
                $parameters
            );
        };

        if (!$varValue) {
            $varValue = $slug->generate(
                (string) ($activeRecord[$titleField] ?? ''),
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
