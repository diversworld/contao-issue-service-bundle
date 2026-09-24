<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Domain\Dto;

final readonly class IssueListFilter 
{ 
    public function __construct(
        public ?int $memberId=null, 
        public ?int $serviceId=null, 
        public ?int $statusId=null, 
        public ?int $assignedUserId=null, 
        public int $page=1, 
        public int $limit=25
    ) {} 
}
