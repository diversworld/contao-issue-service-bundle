<?php
declare(strict_types=1); namespace Vendor\ContaoIssueServiceBundle\Application;
final class TicketPattern { public function render(string $pattern,string $service,int $year,int $sequence):string{if(preg_match('/\{(?!SERVICE\}|YEAR\}|SEQ\})[^}]+\}/',$pattern))throw new \InvalidArgumentException('Unknown placeholder.');$r=strtr($pattern,['{SERVICE}'=>strtoupper($service),'{YEAR}'=>(string)$year,'{SEQ}'=>str_pad((string)$sequence,6,'0',STR_PAD_LEFT)]);if(strlen($r)>64)throw new \LengthException('Ticket number too long.');return $r;} }
