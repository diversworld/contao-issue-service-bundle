<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaEscalationService;
#[AsCommand(name: 'issue:sla:escalate')]
final class SlaEscalateCommand extends Command
{
    public function __construct(private readonly SlaEscalationService $service) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Verarbeitet: '.$this->service->escalate());
        return Command::SUCCESS;
    }
}
