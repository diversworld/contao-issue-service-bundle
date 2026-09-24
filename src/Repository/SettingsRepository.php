<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Repository;

use Doctrine\DBAL\Connection;

final class SettingsRepository 
{ 
    public function __construct(private readonly Connection $db){} 

    public function get(string $key, ?string $default=null): ?string
    {
        $v=$this->db->fetchOne('SELECT setting_value FROM tl_issue_settings WHERE setting_key=:k',['k'=>$key]);
        return false===$v?$default:(string)$v;
    } 

    public function set(string $key,string $type,string $value,?int $userId):void
    {
        $this->db->executeStatement(
            'INSERT INTO tl_issue_settings (tstamp,setting_key,value_type,setting_value,updated_by,updated_at,version) VALUES (:ts,:k,:t,:v,:u,:at,1) ON DUPLICATE KEY UPDATE tstamp=:ts2,value_type=:t2,setting_value=:v2,updated_by=:u2,updated_at=:at2,version=version+1',
            [
                'ts'=>time(),
                'k'=>$key,
                't'=>$type,
                'v'=>$value,
                'u'=>$userId,
                'at'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'ts2'=>time(),
                't2'=>$type,
                'v2'=>$value,
                'u2'=>$userId,
                'at2'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]
        );
    } 
}
