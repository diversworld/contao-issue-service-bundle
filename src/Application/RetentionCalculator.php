<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;

final class RetentionCalculator
{
    public function cutoff(\DateTimeImmutable $now, int $days): \DateTimeImmutable
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('Retention days must be positive.');
        }
        return $now->modify('-' . $days . ' days');
    }
}
