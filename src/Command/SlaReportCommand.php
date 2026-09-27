<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Command;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaReportingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'issue:sla:report')]
final class SlaReportCommand extends Command
{
    public function __construct(private readonly SlaReportingService $reports, private readonly ?\Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaReportExporter $exporter = null) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addOption('month', null, InputOption::VALUE_REQUIRED, 'Monat YYYY-MM');
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'json, pdf, xlsx, csv', 'json');
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Exportdatei (wird nicht überschrieben)');
        $this->addOption('from', null, InputOption::VALUE_REQUIRED, 'Ticketanlage ab YYYY-MM-DD (einschließlich)');
        $this->addOption('until', null, InputOption::VALUE_REQUIRED, 'Ticketanlage vor YYYY-MM-DD (ausschließlich)');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dates = [];
        foreach (['from', 'until'] as $key) {
            $value = $input->getOption($key);
            if ($value !== null && (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || (new \DateTimeImmutable($value))->format('Y-m-d') !== $value)) throw new \InvalidArgumentException('Ungültiges Datum.');
            $dates[$key] = $value;
        }
        if ($dates['from'] !== null && $dates['until'] !== null && $dates['from'] >= $dates['until']) throw new \InvalidArgumentException('Ungültiger Berichtszeitraum.');
        if ($input->getOption('month') !== null) {
            if ($dates['from'] !== null || $dates['until'] !== null) throw new \InvalidArgumentException('--month nicht mit --from/--until kombinieren.');
            [$dates['from'], $dates['until']] = \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaReportExporter::month((string) $input->getOption('month'));
        }
        $report = $this->reports->report(null, $dates['from'], $dates['until']);
        $format = (string) $input->getOption('format');
        $bytes = $format === 'json' ? json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) : ($this->exporter ?? throw new \LogicException('Export service unavailable.'))->export($report, $format);
        $file = $input->getOption('output');
        if ($file === null) {
            if ($format !== 'json') throw new \InvalidArgumentException('--output ist für Exporte erforderlich.');
            $output->writeln($bytes);
        } else {
            $stream = @fopen((string) $file, 'xb');
            if ($stream === false) throw new \RuntimeException('Exportdatei existiert bereits oder ist nicht schreibbar.');
            try { if (fwrite($stream, $bytes) !== strlen($bytes)) throw new \RuntimeException('Exportdatei konnte nicht vollständig geschrieben werden.'); }
            finally { fclose($stream); }
            $output->writeln('SLA-Bericht erstellt.');
        }
        return Command::SUCCESS;
    }
}
