<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Domain\Enum;

enum IssueStatus:string 
{ 
    case New='new'; case Triage='triage'; 
    case InProgress='in_progress'; 
    case WaitingUser='waiting_user'; 
    case Resolved='resolved'; 
    case Closed='closed'; 
    case Rejected='rejected'; 
}
