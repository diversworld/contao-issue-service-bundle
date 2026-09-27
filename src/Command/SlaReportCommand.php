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
    public function __construct(private readonly SlaReportingService $reports) { parent::__construct(); }
    protected function configure(): void
    {
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
        $output->writeln(json_encode($this->reports->report(null, $dates['from'], $dates['until']), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        return Command::SUCCESS;
    }
}
