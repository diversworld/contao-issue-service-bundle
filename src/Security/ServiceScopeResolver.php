<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Security;

use Contao\BackendUser;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class ServiceScopeResolver 
{
    public function __construct(private readonly Connection $db)
    {
    }

    /** @return list<int> */
    public function allowedServiceIds(BackendUser $user):array
    {
        if($user->isAdmin) {
            return array_map('intval',$this->db->fetchFirstColumn('SELECT id FROM tl_issue_service'));
        }
        $groups=array_map('intval',(array)$user->groups);
        if([]===$groups) {
            return [];
        }
        return array_map('intval',$this->db->fetchFirstColumn('SELECT DISTINCT service_id FROM tl_issue_service_group WHERE user_group_id IN (?)',[$groups],[ArrayParameterType::INTEGER]));
    }
}
