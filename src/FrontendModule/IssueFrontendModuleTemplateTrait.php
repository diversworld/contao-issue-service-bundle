<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\StringUtil;

trait IssueFrontendModuleTemplateTrait
{
    private function setModuleTemplateDefaults(FragmentTemplate $template, ModuleModel $model): void
    {
        $cssId = StringUtil::deserialize($model->cssID, true);
        $headline = StringUtil::deserialize($model->headline, true);

        $template->set('element_html_id', $cssId[0] ?? null);
        $template->set('element_css_classes', trim((string) ($cssId[1] ?? '')));
        $template->set('class', trim('mod_'.$model->type.' '.(string) ($cssId[1] ?? '')));
        $template->set('cssID', $cssId[0] ?? '');
        $template->set('type', (string) $model->type);
        $template->set('headline', [
            'text' => (string) ($headline['value'] ?? ''),
            'tag_name' => (string) ($headline['unit'] ?? 'h2'),
        ]);
    }
}