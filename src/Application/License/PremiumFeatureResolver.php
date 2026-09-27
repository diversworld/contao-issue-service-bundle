<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\License;

final class PremiumFeatureResolver
{
    public function __construct(private readonly LicenseService $licenses) {}
    public function enabled(): bool { return $this->licenses->status()['enabled']; }
    public function requireSla(): void
    {
        if (!$this->enabled()) throw new \DomainException('Für SLA-Funktionen wird eine gültige Lizenz benötigt.');
    }
}
