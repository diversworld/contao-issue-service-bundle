<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Application;
use Vendor\ContaoIssueServiceBundle\Repository\SettingsRepository;
final class SettingsService { public function __construct(private readonly SettingsRepository $r){} public function bool(string $k,bool $d=false):bool{return filter_var($this->r->get($k,$d?'1':'0'),FILTER_VALIDATE_BOOL);} public function int(string $k,int $d):int{return (int)$this->r->get($k,(string)$d);} public function json(string $k,array $d=[]):array{$v=json_decode((string)$this->r->get($k,json_encode($d)),true);return is_array($v)?$v:$d;} public function string(string $k,string $d=''):string{return (string)$this->r->get($k,$d);} public function requireLogin():bool{return $this->bool('require_login',true);} public function isComplete():bool{foreach(['ticket_pattern','allowed_extensions','max_file_size','retention_policy','reopen_roles','mail_recipients'] as $k){if(null===$this->r->get($k))return false;}return true;} }
