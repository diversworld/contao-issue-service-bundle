<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vendor\ContaoIssueServiceBundle\Domain\Enum\IssueType;
use Vendor\ContaoIssueServiceBundle\Repository\ServiceRepository;
final class IssueCreateType extends AbstractType { public function __construct(private readonly ServiceRepository $services){} public function buildForm(FormBuilderInterface $b,array $o):void{$b->add('serviceId',ChoiceType::class,['choices'=>array_flip($this->services->choices())])->add('categoryId',TextType::class,['required'=>false])->add('type',ChoiceType::class,['choices'=>array_combine(array_map(fn($e)=>$e->name,IssueType::cases()),array_map(fn($e)=>$e->value,IssueType::cases()))])->add('title',TextType::class,['constraints'=>[new Assert\Length(min:3,max:255)]])->add('description',TextareaType::class,['constraints'=>[new Assert\Length(min:10,max:50000)]])->add('attachments',FileType::class,['mapped'=>false,'multiple'=>true,'required'=>false])->add('submit',SubmitType::class);} public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['csrf_protection'=>true,'csrf_token_id'=>'issue_create']);} }
