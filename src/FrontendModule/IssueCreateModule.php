<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\Module;
use Contao\StringUtil;
use Contao\System;

final class IssueCreateModule extends Module
{
    protected function compile(): void
    {
    }

    public function generate(): string
    {
        $router = System::getContainer()->get('router');
        $label = $GLOBALS['TL_LANG']['MSC']['issue_service_create_link'] ?? 'Neues Issue erstellen';

        return sprintf(
            '<div class="mod_issue_service_create%s"><p><a class="button" href="%s">%s</a></p></div>',
            '' !== ($this->cssID[1] ?? '') ? ' '.StringUtil::specialchars($this->cssID[1]) : '',
            StringUtil::specialchars($router->generate('issue_service_create')),
            StringUtil::specialchars($label),
        );
    }
}