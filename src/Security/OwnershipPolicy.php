<?php
declare(strict_types=1); namespace Vendor\ContaoIssueServiceBundle\Security;
final class OwnershipPolicy { public function mayAccess(?int $ownerId,?int $currentMemberId,?string $storedHash,?string $presentedToken):bool{if(null!==$ownerId&&null!==$currentMemberId)return $ownerId===$currentMemberId;if(null!==$storedHash&&null!==$presentedToken)return hash_equals($storedHash,hash('sha256',$presentedToken));return false;} }
