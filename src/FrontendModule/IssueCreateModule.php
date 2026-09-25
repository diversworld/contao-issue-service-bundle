<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Diversworld\ContaoIssueServiceBundle\Application\AttachmentService;
use Diversworld\ContaoIssueServiceBundle\Application\IssueApplicationService;
use Diversworld\ContaoIssueServiceBundle\Domain\Dto\CreateIssueCommand;
use Diversworld\ContaoIssueServiceBundle\Form\IssueCreateType;
use Diversworld\ContaoIssueServiceBundle\Routing\IssueDetailUrlGenerator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(IssueCreateModule::TYPE, category: 'issue_service', template: 'frontend_module/issue_service_create')]
final class IssueCreateModule extends AbstractFrontendModuleController
{
    use IssueFrontendModuleTemplateTrait;

    public const TYPE = 'issue_service_create';

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly IssueApplicationService $issues,
        private readonly AttachmentService $attachments,
        private readonly IssueDetailUrlGenerator $detailUrlGenerator,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        #[Autowire(param: 'contao.csrf_token_name')]
        private readonly string $csrfTokenName,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->setModuleTemplateDefaults($template, $model);

        $user = FrontendUser::getInstance();

        if (!$user->id) {
            $template->set('loginRequired', true);
            $template->set('loginRequiredMessage', $GLOBALS['TL_LANG']['MSC']['issue_service_login_required'] ?? 'Bitte melden Sie sich an, um ein Issue zu erstellen.');

            return $template->getResponse();
        }

        $form = $this->formFactory->create(IssueCreateType::class, null, $this->getContaoCsrfFormOptions());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $directory = (string) $model->issue_attachment_directory;
            if ($model->issue_attachment_storage === 'files') {
                if (!$model->issue_attachment_folder) {
                    throw new \RuntimeException('Bitte im Erstellungsmodul einen Ablageordner für Anhänge auswählen.');
                }
                $uuid = \Contao\StringUtil::binToUuid($model->issue_attachment_folder);
                \Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage::resolveFolder($uuid);
                $directory = 'files:'.$uuid;
            }
            $data = $form->getData();
            $issue = $this->issues->create(
                new CreateIssueCommand(
                    (int) $user->id,
                    (int) $data['serviceId'],
                    $data['categoryId'] ? (int) $data['categoryId'] : null,
                    (string) $data['type'],
                    trim((string) $data['title']),
                    trim((string) $data['description']),
                    priority: (string) $data['priority'],
                ),
            );

            foreach ($form->get('attachments')->getData() ?? [] as $file) {
                $this->attachments->upload($issue['id'], $file, 'member', (int) $user->id, directory: $directory);
            }

            return new RedirectResponse($this->generateDetailUrl((string) $issue['uuid'], $model));
        }

        $template->set('loginRequired', false);
        $template->set('form', $form->createView());
        $template->set('action', $request->getUri());

        $response = $template->getResponse();
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'no-cache, no-store');

        return $response;
    }

    private function generateDetailUrl(string $uuid, ModuleModel $model): string
    {
        return $this->detailUrlGenerator->generate($uuid, $model);
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
