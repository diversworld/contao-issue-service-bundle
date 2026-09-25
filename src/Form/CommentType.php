<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Form;

use Diversworld\ContaoIssueServiceBundle\Application\AttachmentConstraints;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{SubmitType, TextareaType, FileType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class CommentType extends AbstractType
{
    public function __construct(private readonly AttachmentConstraints $uploads) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, ['label' => 'Antwort', 'required' => false, 'constraints' => [new Assert\Length(max: 20000)]])
            ->add('attachments', FileType::class, ['label' => 'Anhänge ergänzen', 'multiple' => true, 'required' => false, 'constraints' => $this->uploads->all(), 'help' => 'Erlaubte Dateitypen: '.implode(', ', $this->uploads->extensions())])
            ->add('submit', SubmitType::class, ['label' => 'Absenden']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_protection' => true, 'csrf_token_id' => 'issue_comment',
            'constraints' => [new Assert\Callback(static function (array $data, \Symfony\Component\Validator\Context\ExecutionContextInterface $context): void {
                if (trim((string) ($data['body'] ?? '')) === '' && empty($data['attachments'])) {
                    $context->buildViolation('Bitte eine Antwort eingeben oder einen Anhang auswählen.')->atPath('[body]')->addViolation();
                }
            })],
        ]);
    }
}
