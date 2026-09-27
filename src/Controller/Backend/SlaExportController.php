<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Controller\Backend;

use Contao\BackendUser;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\{SlaReportingService, SlaReportExporter};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Exception\{AccessDeniedHttpException, BadRequestHttpException};
use Symfony\Component\Routing\Attribute\Route;

#[Route('%contao.backend.route_prefix%/issue-sla/export/{format}', name: 'issue_sla_export', defaults: ['_scope' => 'backend'], methods: ['GET'], requirements: ['format' => 'pdf|xlsx|csv'])]
final class SlaExportController
{
    public function __invoke(Request $request, string $format, SlaReportingService $reports, SlaReportExporter $exporter): Response
    {
        $user = BackendUser::getInstance();
        if (!$user instanceof BackendUser || !$user->isAdmin) throw new AccessDeniedHttpException();
        $month = (string) $request->query->get('month', date('Y-m'));
        try { [$from, $until] = SlaReportExporter::month($month); }
        catch (\InvalidArgumentException $error) { throw new BadRequestHttpException($error->getMessage(), $error); }
        $bytes = $exporter->export($reports->report(null, $from, $until), $format);
        return new Response($bytes, 200, ['Content-Type' => match ($format) { 'pdf' => 'application/pdf', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', default => 'text/csv; charset=UTF-8' }, 'Content-Disposition' => 'attachment; filename="sla-'.$month.'.'.$format.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
