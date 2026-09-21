<?php
declare(strict_types=1); namespace Vendor\ContaoIssueServiceBundle\Application;
final class SettingsParser { public function bool(?string $v,bool $d=false):bool{return null===$v?$d:filter_var($v,FILTER_VALIDATE_BOOL);} public function positiveInt(?string $v,int $d):int{$i=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return false===$i?$d:$i;} public function jsonArray(?string $v,array $d=[]):array{if(null===$v)return $d;try{$r=json_decode($v,true,512,JSON_THROW_ON_ERROR);return is_array($r)&&array_is_list($r)?$r:$d;}catch(\JsonException){return $d;}} }
