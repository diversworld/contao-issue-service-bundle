<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Form;

use Diversworld\ContaoIssueServiceBundle\Repository\ServiceRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class IssueCreateType extends AbstractType
{
    public function __construct(private readonly ServiceRepository $services)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = array_column($this->services->categoryChoices(), null, 'id');

        $builder
            ->add('serviceId', ChoiceType::class, [
                'label' => 'Service',
                'attr' => ['data-issue-service' => ''],
                'choices' => array_flip($this->services->choices()),
            ])
            ->add('categoryId', ChoiceType::class, [
                'label' => 'Kategorie',
                'attr' => ['data-issue-category' => ''],
                'required' => false,
                'placeholder' => 'Keine Kategorie',
                'choices' => array_keys($categories),
                'choice_label' => static fn (int $id): string => $categories[$id]['title'],
                'choice_attr' => static fn (int $id): array => ['data-service-id' => (string) $categories[$id]['service_id']],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Typ',
                'choices' => ['Störung' => 'incident', 'Fehler' => 'bug', 'Verbesserung' => 'improvement', 'Anfrage' => 'request', 'Frage' => 'question'],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorität',
                'choices' => ['Niedrig' => 'low', 'Normal' => 'normal', 'Hoch' => 'high', 'Kritisch' => 'critical'],
                'data' => 'normal',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 255)],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10, max: 50000)],
            ])
            ->add('attachments', FileType::class, ['label' => 'Anhänge', 'mapped' => false, 'multiple' => true, 'required' => false])
            ->add('submit', SubmitType::class, ['label' => 'Issue erstellen']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id' => 'issue_create',
            'constraints' => [new Assert\Callback(function (array $data, ExecutionContextInterface $context): void {
                if (isset($data['categoryId'], $data['serviceId']) && !$this->services->categoryBelongsToService((int) $data['categoryId'], (int) $data['serviceId'])) {
                    $context->buildViolation('Bitte wählen Sie eine Kategorie des ausgewählten Services.')
                        ->atPath('[categoryId]')->addViolation();
                }
            })],
        ]);
    }
}
