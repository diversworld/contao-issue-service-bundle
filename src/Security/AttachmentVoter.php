<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Security;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
final class AttachmentVoter extends Voter { public const DOWNLOAD='attachment.download'; protected function supports(string $a,mixed $s):bool{return self::DOWNLOAD===$a&&is_array($s)&&isset($s['member_id']);} protected function voteOnAttribute(string $a,mixed $s,TokenInterface $t):bool{$u=$t->getUser();return $u instanceof FrontendUser&&(int)$u->id===(int)$s['member_id'];} }
