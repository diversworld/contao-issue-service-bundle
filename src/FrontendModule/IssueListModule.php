<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsFrontendModule(IssueListModule::TYPE, category: 'issue_service', template: 'frontend_module/issue_service_list')]
final class IssueListModule extends AbstractFrontendModuleController
{
    use IssueFrontendModuleTemplateTrait;

    public const TYPE = 'issue_service_list';

    private const UUID_SQL = "LOWER(CONCAT(SUBSTR(HEX(i.uuid),1,8),'-',SUBSTR(HEX(i.uuid),9,4),'-',SUBSTR(HEX(i.uuid),13,4),'-',SUBSTR(HEX(i.uuid),17,4),'-',SUBSTR(HEX(i.uuid),21)))";

    public function __construct(
        private readonly Connection $connection,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->setModuleTemplateDefaults($template, $model);

        $user = FrontendUser::getInstance();

        $template->set('loginRequired', !$user->id);
        $template->set('loginRequiredMessage', $GLOBALS['TL_LANG']['MSC']['issue_service_login_required'] ?? 'Bitte melden Sie sich an, um Ihre Issues zu sehen.');
        $template->set('emptyMessage', $GLOBALS['TL_LANG']['MSC']['issue_service_no_issues'] ?? 'Es sind keine Issues vorhanden.');
        $template->set('issues', []);

        if (!$user->id) {
            return $template->getResponse();
        }

        $issues = $this->connection->fetchAllAssociative(
            'SELECT i.ticket_number, i.title, i.last_public_activity_at, '.self::UUID_SQL.' uuid, s.title service_title, st.title status_title
             FROM tl_issue i
             JOIN tl_issue_service s ON s.id = i.service_id
             JOIN tl_issue_status st ON st.id = i.status_id
             WHERE i.deleted_at IS NULL AND i.member_id = :member
             ORDER BY i.last_public_activity_at DESC, i.id DESC
             LIMIT 20',
            ['member' => (int) $user->id],
        );

        foreach ($issues as &$issue) {
            $issue['detail_url'] = $this->generateDetailUrl((string) $issue['uuid'], $model);
        }
        unset($issue);

        $template->set('issues', $issues);

        return $template->getResponse();
    }

    private function generateDetailUrl(string $uuid, ModuleModel $model): string
    {
        $page = PageModel::findByPk((int) $model->jumpTo);

        if (null !== $page) {
            return $this->contentUrlGenerator->generate($page, ['uuid' => $uuid]);
        }

        return $this->urlGenerator->generate('issue_service_detail', ['uuid' => $uuid]);
    }
}
    