<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\Module;
use Contao\StringUtil;
use Contao\System;

final class IssueDetailModule extends Module
{
    protected function compile(): void
    {
    }

    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        $uuid = $request?->attributes->get('uuid') ?: $request?->query->get('uuid');

        if (!$uuid) {
            return '<div class="mod_issue_service_detail"><p class="empty">'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['issue_service_no_issue_selected'] ?? 'Kein Issue ausgewaehlt.').'</p></div>';
        }

        $router = System::getContainer()->get('router');

        return sprintf(
            '<div class="mod_issue_service_detail"><p><a href="%s">%s</a></p></div>',
            StringUtil::specialchars($router->generate('issue_service_detail', ['uuid' => (string) $uuid])),
            StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['issue_service_open_detail'] ?? 'Issue-Details oeffnen'),
        );
    }
}