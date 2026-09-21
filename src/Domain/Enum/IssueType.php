<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Domain\Enum;
enum IssueType:string { case Incident='incident'; case Bug='bug'; case Improvement='improvement'; case Request='request'; case Question='question'; }
