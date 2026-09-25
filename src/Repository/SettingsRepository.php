<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Repository;

use Doctrine\DBAL\Connection;

final class SettingsRepository 
{ 
    public function __construct(private readonly Connection $db){} 

    public function profile(int $id = 0): ?array
    {
        $row = $id > 0
            ? $this->db->fetchAssociative('SELECT * FROM tl_issue_profile WHERE id=:id', ['id' => $id])
            : $this->db->fetchAssociative('SELECT * FROM tl_issue_profile ORDER BY id LIMIT 1');
        if (!$row && $id > 0) {
            throw new \InvalidArgumentException('Das ausgewählte Konfigurationsprofil existiert nicht.');
        }
        return $row ?: null;
    }

    public function get(string $key, ?string $default = null, int $profileId = 0): ?string
    {
        $profile = $this->profile($profileId);
        if ($profile) {
            if ($key === 'retention_policy') {
                return json_encode(['closed_days' => (int) $profile['retention_days']], JSON_THROW_ON_ERROR);
            }
            if (in_array($key, ['allowed_extensions', 'reopen_roles'], true)) {
                return json_encode(\Diversworld\ContaoIssueServiceBundle\Application\SettingsList::decode($profile[$key] ?? null), JSON_THROW_ON_ERROR);
            }
            if ($key === 'mail_recipients') {
                return json_encode(array_values(array_filter(array_map('trim', preg_split('/\R+/', $profile[$key] ?? '') ?: []))), JSON_THROW_ON_ERROR);
            }
            return isset($profile[$key]) ? (string) $profile[$key] : $default;
        }
        $value = $this->db->fetchOne('SELECT setting_value FROM tl_issue_settings WHERE setting_key=:k', ['k' => $key]);
        return false === $value ? $default : (string) $value;
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
