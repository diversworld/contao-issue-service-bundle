<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Form;
use Symfony\Component\Form\AbstractType;use Symfony\Component\Form\Extension\Core\Type\SubmitType;use Symfony\Component\Form\Extension\Core\Type\TextareaType;use Symfony\Component\Form\FormBuilderInterface;use Symfony\Component\OptionsResolver\OptionsResolver;use Symfony\Component\Validator\Constraints as Assert;
final class CommentType extends AbstractType { public function buildForm(FormBuilderInterface $b,array $o):void{$b->add('body',TextareaType::class,['constraints'=>[new Assert\Length(min:1,max:20000)]])->add('submit',SubmitType::class);} public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['csrf_protection'=>true,'csrf_token_id'=>'issue_comment']);} }
