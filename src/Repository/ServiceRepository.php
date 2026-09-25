<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Repository;

use Doctrine\DBAL\Connection;

final class ServiceRepository 
{ 
    public function __construct(private readonly Connection $db){} 

    /** @return array<int|string, mixed> */ 
    public function choices():array
    {
        return $this->db->fetchAllKeyValue("SELECT id,title FROM tl_issue_service WHERE published=1 ORDER BY title");
    } 

    /** @return list<array<string, mixed>> */
    public function categoryChoices(): array
    {
        return $this->db->fetchAllAssociative('SELECT c.id, c.title, c.service_id, s.title AS service_title FROM tl_issue_category c INNER JOIN tl_issue_service s ON s.id=c.service_id WHERE c.published=1 AND s.published=1 ORDER BY s.title, c.title, c.id');
    }

    public function categoryBelongsToService(?int $categoryId,int $serviceId):bool
    {
        return null===$categoryId||false!==$this->db->fetchOne('SELECT 1 FROM tl_issue_category WHERE id=:c AND service_id=:s AND published=1',['c'=>$categoryId,'s'=>$serviceId]);
    } 
}
