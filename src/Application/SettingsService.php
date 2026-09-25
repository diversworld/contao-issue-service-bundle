<?php
declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
final class SettingsService { public function __construct(private readonly SettingsRepository $r, private readonly int $profileId = 0){} public function forProfile(int $id): self { return new self($this->r, $id); }
 public function profile(): ?array { return $this->r->profile($this->profileId); }
 private function get(string $key, ?string $default = null): ?string { return $this->r->get($key, $default, $this->profileId); }
 public function bool(string $k,bool $d=false):bool{return filter_var($this->get($k,$d?'1':'0'),FILTER_VALIDATE_BOOL);} public function int(string $k,int $d):int{return (int)$this->get($k,(string)$d);} /**
 * @param array<mixed> $d
 * @return array<mixed>
 */ public function json(string $k,array $d=[]):array{$v=json_decode((string)$this->get($k,json_encode($d,JSON_THROW_ON_ERROR)),true);return is_array($v)?$v:$d;} public function string(string $k,string $d=''):string{return (string)$this->get($k,$d);} public function requireLogin():bool{return $this->bool('require_login',true);} public function isComplete():bool{foreach(['ticket_pattern','allowed_extensions','max_file_size','retention_policy','reopen_roles','mail_recipients'] as $k){if(null===$this->get($k))return false;}return true;} }
