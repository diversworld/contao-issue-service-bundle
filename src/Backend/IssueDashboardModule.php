<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Backend;

use Contao\BackendModule;
use Contao\DataContainer;
use Contao\System;
use Diversworld\ContaoIssueServiceBundle\Application\SettingsService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('contao.backend_module', ['module' => 'issue_service_dashboard'])]
final class IssueDashboardModule extends BackendModule
{
    private SettingsService $settings;

    public function __construct(?DataContainer $dc = null)
    {
        parent::__construct($dc);

        $this->settings = System::getContainer()->get(SettingsService::class);
    }

    public function generate(): string
    {
        $message = $this->settings->isComplete()
            ? '<p class="tl_confirm">Die Pflichtkonfiguration ist vollstaendig.</p>'
            : '<p class="tl_error">Die Pflichtkonfiguration ist unvollstaendig.</p>';

        return '<div class="tl_listing_container"><h2>Issue &amp; Service Management</h2>'.$message.'</div>';
    }

    protected function compile(): void
    {
    }
}