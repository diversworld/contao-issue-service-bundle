<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Routing;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\ModuleModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class IssueDetailUrlGenerator
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generate(string $uuid, ModuleModel|null $model = null): string
    {
        if (null !== $model && ($model->jumpTo ?? 0)) {
            $page = PageModel::findByPk((int) $model->jumpTo);

            if (null !== $page) {
                return $this->contentUrlGenerator->generate($page, ['uuid' => $uuid]);
            }
        }

        $pageId = $this->connection->fetchOne(
            "SELECT p.id
             FROM tl_module m
             JOIN tl_content c ON c.module = m.id AND c.type = 'module'
             JOIN tl_article a ON a.id = c.pid
             JOIN tl_page p ON p.id = a.pid
             WHERE m.type = 'issue_service_detail'
             ORDER BY p.sorting, p.id
             LIMIT 1",
        );

        if ($pageId) {
            $page = PageModel::findByPk((int) $pageId);

            if (null !== $page) {
                return $this->contentUrlGenerator->generate($page, ['uuid' => $uuid]);
            }
        }

        return $this->urlGenerator->generate('issue_service_detail', ['uuid' => $uuid]);
    }
}