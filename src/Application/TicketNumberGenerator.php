<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Application;
use Doctrine\DBAL\Connection;
final class TicketNumberGenerator { public function __construct(private readonly Connection $db,private readonly SettingsService $settings){} public function next(string $serviceAlias):string{$year=(new \DateTimeImmutable())->format('Y');$this->db->executeStatement('INSERT INTO tl_issue_sequence (sequence_key,current_value) VALUES (:k,1) ON DUPLICATE KEY UPDATE current_value=LAST_INSERT_ID(current_value+1)',['k'=>$serviceAlias.'-'.$year]);$seq=(int)$this->db->fetchOne('SELECT current_value FROM tl_issue_sequence WHERE sequence_key=:k',['k'=>$serviceAlias.'-'.$year]);return strtr($this->settings->string('ticket_pattern','{SERVICE}-{YEAR}-{SEQ}'),['{SERVICE}'=>strtoupper($serviceAlias),'{YEAR}'=>$year,'{SEQ}'=>str_pad((string)$seq,6,'0',STR_PAD_LEFT)]);} }
