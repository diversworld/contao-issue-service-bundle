<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Domain\Enum;

enum CommentVisibility:string 
{ 
    case Public='public'; 
    case Internal='internal'; 
}
