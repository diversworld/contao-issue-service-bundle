<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Diversworld\ContaoIssueServiceBundle\Form\CommentType;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Diversworld\ContaoIssueServiceBundle\Security\IssueVoter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsFrontendModule(IssueDetailModule::TYPE, category: 'issue_service', template: 'frontend_module/issue_service_detail')]
final class IssueDetailModule extends AbstractFrontendModuleController
{
    use IssueFrontendModuleTemplateTrait;

    public const TYPE = 'issue_service_detail';

    public function __construct(
        private readonly IssueRepository $issues,
        private readonly FormFactoryInterface $formFactory,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        #[Autowire(param: 'contao.csrf_token_name')]
        private readonly string $csrfTokenName,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->setModuleTemplateDefaults($template, $model);

        $uuid = (string) ($request->attributes->get('uuid') ?: $request->query->get('uuid', ''));

        if ('' === $uuid) {
            $template->set('issue', null);
            $template->set('emptyMessage', $GLOBALS['TL_LANG']['MSC']['issue_service_no_issue_selected'] ?? 'Kein Issue ausgewaehlt.');

            return $template->getResponse();
        }

        $user = FrontendUser::getInstance();
        $memberId = $user->id ? (int) $user->id : null;
        $issue = $this->issues->findAuthorized($uuid, $memberId, null);

        if (null === $issue) {
            $template->set('issue', null);
            $template->set('emptyMessage', $GLOBALS['TL_LANG']['MSC']['issue_service_no_issue_selected'] ?? 'Kein Issue ausgewaehlt.');

            return $template->getResponse();
        }

        if (!$this->authorizationChecker->isGranted(IssueVoter::VIEW, $issue)) {
            throw new AccessDeniedHttpException();
        }

        $template->set('issue', $issue);
        $template->set('attachments', $this->issues->attachments((int) $issue['id']));
        $template->set('timeline', $this->issues->publicTimeline((int) $issue['id']));
        $template->set('commentForm', $this->formFactory->create(CommentType::class, null, $this->getContaoCsrfFormOptions() + ['profile_id' => (int) ($issue['profile_id'] ?? 0)])->createView());

        $response = $template->getResponse();
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'no-cache, no-store');

        return $response;
    }

    /** @return array{csrf_field_name: string, csrf_token_manager: ContaoCsrfTokenManager, csrf_token_id: string} */
    private function getContaoCsrfFormOptions(): array
    {
        return [
            'csrf_field_name' => 'REQUEST_TOKEN',
            'csrf_token_manager' => $this->csrfTokenManager,
            'csrf_token_id' => $this->csrfTokenName,
        ];
    }
}
