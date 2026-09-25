<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Domain\Dto;

final readonly class CreateIssueCommand 
{ 
    public function __construct(
        public ?int $memberId, 
        public int $serviceId, 
        public ?int $categoryId, 
        public string $type, 
        public string $title, 
        public string $description, 
        public ?string $guestAccessHash=null,
        public string $priority='normal')
    {} 
}
