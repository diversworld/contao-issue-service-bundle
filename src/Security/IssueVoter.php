<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Security;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
final class IssueVoter extends Voter { public const VIEW='issue.view';public const COMMENT='issue.comment'; protected function supports(string $attribute,mixed $subject):bool{return in_array($attribute,[self::VIEW,self::COMMENT],true)&&is_array($subject)&&isset($subject['id']);} protected function voteOnAttribute(string $attribute,mixed $issue,TokenInterface $token):bool{$u=$token->getUser();return $u instanceof FrontendUser && (int)$issue['member_id']===(int)$u->id;} }
