<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Doctrine\DBAL\Connection;

trait AliasHandlerTrait
{
    /** @param list<string> $scopeFields */
    protected function updateAliasAfterCopy(
        Connection $db,
        Slug $slug,
        int|string $insertId,
        string $table,
        string $titleField = 'title',
        string $aliasField = 'alias',
        array $scopeFields = []
    ): void
    {
        $record = $db->fetchAssociative(sprintf('SELECT * FROM %s WHERE id=?', $table), [$insertId]);

        if (false === $record) {
            return;
        }

        $aliasExists = function (string $alias) use ($db, $insertId, $table, $aliasField, $scopeFields, $record): bool {
            $conditions = ["$aliasField=?", 'id!=?'];
            $parameters = [$alias, $insertId];

            foreach ($scopeFields as $scopeField) {
                $conditions[] = "$scopeField=?";
                $parameters[] = $record[$scopeField] ?? null;
            }

            return (bool) $db->fetchOne(
                sprintf('SELECT id FROM %s WHERE %s LIMIT 1', $table, implode(' AND ', $conditions)),
                $parameters
            );
        };

        $db->update(
            $table,
            [$aliasField => $slug->generate((string) ($record[$titleField] ?? ''), [], $aliasExists)],
            ['id' => $insertId]
        );
    }

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
        $activeRecord ??= [];
        // The title may follow the alias field in the palette (e.g. status_key).
        if (Input::post('FORM_SUBMIT') === $table) {
            foreach (array_merge([$titleField], $scopeFields) as $field) {
                $submitted = Input::post($field);
                if (is_scalar($submitted)) $activeRecord[$field] = $submitted;
            }
        }


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
