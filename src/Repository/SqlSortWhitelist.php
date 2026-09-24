<?php 

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Repository;

final class SqlSortWhitelist 
{ 
    private const ALLOWED=['ticket_number','last_public_activity_at','priority','created_at']; 

    /** @return array{string, 'ASC'|'DESC'} */ 
    public function normalize(string $field,string $direction):array
    {
        return [
            in_array($field,self::ALLOWED,true)?$field:'last_public_activity_at',
            'ASC'===strtoupper($direction)?'ASC':'DESC'
        ];
    } 
}
