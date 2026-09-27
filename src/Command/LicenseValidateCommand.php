<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Command;
use Diversworld\ContaoIssueServiceBundle\Application\License\LicenseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'issue:license:validate')]
final class LicenseValidateCommand extends Command
{
    public function __construct(private readonly LicenseService $licenses) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addOption('offline-file', null, InputOption::VALUE_REQUIRED, 'Signierte Lizenzdatei importieren.');
        $this->addOption('online', null, InputOption::VALUE_NONE, 'Lizenzserver kontaktieren.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($file = $input->getOption('offline-file')) {
            if (!is_string($file) || !is_file($file) || !is_readable($file) || filesize($file) > 65536) throw new \InvalidArgumentException('Lizenzdatei nicht lesbar oder zu groß.');
            $token = file_get_contents($file);
            if ($token === false) throw new \RuntimeException('Lizenzdatei nicht lesbar.');
            $this->licenses->import(trim($token));
        }
        $status = $input->getOption('online') ? $this->licenses->refresh() : $this->licenses->status();
        $output->writeln('Lizenz: '.$status['state']);
        return $status['enabled'] ? Command::SUCCESS : Command::FAILURE;
    }
}
