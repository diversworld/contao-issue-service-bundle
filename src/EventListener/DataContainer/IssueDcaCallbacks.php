<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final class IssueDcaCallbacks
{
    public function updateGeneratedFields(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $connection = System::getContainer()->get('database_connection');
        \assert($connection instanceof Connection);

        $issue = $connection->fetchAssociative('SELECT id, uuid, ticket_number, created_at, updated_at, last_public_activity_at FROM tl_issue WHERE id = :id', ['id' => $dc->id]);

        if (false === $issue) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $values = [
            'updated_at' => $now,
        ];

        if (null === $issue['uuid']) {
            $values['uuid'] = Uuid::v4()->toBinary();
        }

        if (null === $issue['ticket_number'] || '' === (string) $issue['ticket_number']) {
            $values['ticket_number'] = sprintf('BE-%s-%06d', date('Y'), (int) $dc->id);
        }

        foreach (['created_at', 'last_public_activity_at'] as $field) {
            if (empty($issue[$field]) || '0000-00-00 00:00:00' === $issue[$field]) {
                $values[$field] = $now;
            }
        }

        $connection->update('tl_issue', $values, ['id' => $dc->id]);
    }
}