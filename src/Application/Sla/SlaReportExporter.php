<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Diversworld\ContaoIssueServiceBundle\Application\License\PremiumFeatureResolver;

final class SlaReportExporter
{
    public function __construct(private readonly PremiumFeatureResolver $premium) {}

    /** @return array{string, string} */
    public static function month(string $month): array
    {
        if (!preg_match('/^\d{4}-(?:0[1-9]|1[0-2])$/D', $month)) throw new \InvalidArgumentException('Monat muss YYYY-MM entsprechen.');
        $start = new \DateTimeImmutable($month.'-01');
        return [$start->format('Y-m-d'), $start->modify('+1 month')->format('Y-m-d')];
    }

    /** @param array<string, mixed> $report
     * @return list<list<string|int|float|null>> */
    public function rows(array $report): array
    {
        $rows = [['SLA-Bericht', $report['from'] ?? '', $report['until'] ?? ''], ['Kennzahl', 'Wert']];
        foreach (['total'=>'Ticketvolumen', 'breached'=>'SLA-Verletzungen', 'completed'=>'Abgeschlossen', 'completed_on_time'=>'Fristgerecht abgeschlossen', 'compliance_percent'=>'Erfüllungsquote (%)', 'average_response_seconds'=>'Ø Reaktionszeit (Supportsekunden)', 'average_resolve_seconds'=>'Ø Lösungszeit (Supportsekunden)', 'critical'=>'Kritische Tickets', 'open_escalations'=>'Offene Eskalationen'] as $key=>$label) $rows[] = [$label, $report['metrics'][$key] ?? null];
        $rows[] = []; $rows[] = ['Kategorie', 'Ticketvolumen'];
        foreach ($report['categories'] as $row) $rows[] = [$row['name'], $row['tickets']];
        $rows[] = []; $rows[] = ['Agent', 'Tickets', 'Verletzungen', 'Abgeschlossen', 'Ø Reaktion (s)', 'Ø Lösung (s)'];
        foreach ($report['agents'] as $row) $rows[] = [$row['name'], $row['tickets'], $row['breached'], $row['completed'], $row['average_response_seconds'], $row['average_resolve_seconds']];
        return $rows;
    }

    /** @param array<string, mixed> $report */
    public function export(array $report, string $format): string
    {
        $this->premium->requireSla();
        $rows = $this->rows($report);
        return match ($format) {
            'csv' => $this->csv($rows),
            'xlsx' => $this->xlsx($rows),
            'pdf' => $this->pdf($rows),
            default => throw new \InvalidArgumentException('Exportformat muss pdf, xlsx oder csv sein.'),
        };
    }

    /** @param list<list<string|int|float|null>> $rows */
    private function csv(array $rows): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) throw new \RuntimeException('Export konnte nicht erstellt werden.');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) fputcsv($stream, array_map(static fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row), ';', '"', '');
        rewind($stream); $result = stream_get_contents($stream); fclose($stream);
        if ($result === false) throw new \RuntimeException('Export konnte nicht gelesen werden.');
        return $result;
    }

    /** Small OOXML workbook, with explicit string cells to prevent formula execution.
     * @param list<list<string|int|float|null>> $rows */
    private function xlsx(array $rows): string
    {
        $file = tempnam(sys_get_temp_dir(), 'sla-xlsx-');
        if ($file === false) throw new \RuntimeException('Temporäre Datei nicht verfügbar.');
        try {
            $zip = new \ZipArchive();
            if ($zip->open($file, \ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('XLSX konnte nicht erstellt werden.');
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="SLA" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
            foreach ($rows as $i => $row) {
                $xml .= '<row r="'.($i + 1).'">';
                foreach ($row as $j => $value) {
                    $ref = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[$j % 26].($i + 1);
                    $xml .= is_int($value) || is_float($value) ? '<c r="'.$ref.'"><v>'.$value.'</v></c>' : '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
                }
                $xml .= '</row>';
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $xml.'</sheetData></worksheet>');
            $zip->close();
            $bytes = file_get_contents($file);
            if ($bytes === false) throw new \RuntimeException('XLSX konnte nicht gelesen werden.');
            return $bytes;
        } finally { unlink($file); }
    }

    /** @param list<list<string|int|float|null>> $rows */
    private function pdf(array $rows): string
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
        $pdf->SetCreator('Contao Issue Service'); $pdf->SetTitle('SLA-Bericht');
        $pdf->SetFont('dejavusans', '', 10); $pdf->AddPage();
        $html = '<h1>SLA-Bericht</h1><table cellpadding="5" border="1">';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row ?: [''] as $value) $html .= '<td>'.htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</td>';
            $html .= '</tr>';
        }
        $pdf->writeHTML($html.'</table>');
        return $pdf->Output('sla-report.pdf', 'S');
    }
}
