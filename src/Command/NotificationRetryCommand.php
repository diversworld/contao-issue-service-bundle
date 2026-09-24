<?php
declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Command;
use Symfony\Component\Console\Attribute\AsCommand;use Symfony\Component\Console\Command\Command;use Symfony\Component\Console\Input\InputInterface;use Symfony\Component\Console\Output\OutputInterface;use Diversworld\ContaoIssueServiceBundle\Application\NotificationService;
#[AsCommand(name:'issue-service:notifications:dispatch')] final class NotificationRetryCommand extends Command { public function __construct(private readonly NotificationService $svc){parent::__construct();} protected function execute(InputInterface $i,OutputInterface $o):int{$o->writeln('Sent: '.$this->svc->dispatchPending());return Command::SUCCESS;} }
