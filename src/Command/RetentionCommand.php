<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Command;
use Symfony\Component\Console\Attribute\AsCommand;use Symfony\Component\Console\Command\Command;use Symfony\Component\Console\Input\InputInterface;use Symfony\Component\Console\Input\InputOption;use Symfony\Component\Console\Output\OutputInterface;use Vendor\ContaoIssueServiceBundle\Application\RetentionService;
#[AsCommand(name:'issue-service:retention')] final class RetentionCommand extends Command { public function __construct(private readonly RetentionService $svc){parent::__construct();} protected function configure():void{$this->addOption('execute',null,InputOption::VALUE_NONE,'Apply changes; default is dry-run.');} protected function execute(InputInterface $i,OutputInterface $o):int{$r=$this->svc->execute(!$i->getOption('execute'));$o->writeln(json_encode($r,JSON_PRETTY_PRINT));return Command::SUCCESS;} }
