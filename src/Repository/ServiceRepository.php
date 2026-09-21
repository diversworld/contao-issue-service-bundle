<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Repository;
use Doctrine\DBAL\Connection;
final class ServiceRepository { public function __construct(private readonly Connection $db){} public function choices():array{return $this->db->fetchAllKeyValue("SELECT id,title FROM tl_issue_service WHERE published=1 ORDER BY title");} public function categoryBelongsToService(?int $categoryId,int $serviceId):bool{return null===$categoryId||false!==$this->db->fetchOne('SELECT 1 FROM tl_issue_category WHERE id=:c AND service_id=:s AND published=1',['c'=>$categoryId,'s'=>$serviceId]);} }
