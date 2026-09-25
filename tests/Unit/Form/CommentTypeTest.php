<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Form;

use Diversworld\ContaoIssueServiceBundle\Application\{AttachmentConstraints, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
use Diversworld\ContaoIssueServiceBundle\Form\CommentType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class CommentTypeTest extends TestCase
{
    public function testTextOrAttachmentRequiredAndInvalidFilesRejected(): void
    {
        $db = $this->createStub(Connection::class);
        $db->method('fetchOne')->willReturn(false);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new \Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new CommentType(new AttachmentConstraints(new SettingsService(new SettingsRepository($db)))))
            ->getFormFactory();
        $path = tempnam(sys_get_temp_dir(), 'reply-');
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        try {
            $statusForm = $factory->create(CommentType::class, null, ['status_choices' => [2 => 'Gelöst']]);
            $statusForm->submit(['body' => '', 'attachments' => [], 'targetStatus' => '2']);
            self::assertTrue($statusForm->isValid(), (string) $statusForm->getErrors(true));
            $invalidStatus = $factory->create(CommentType::class, null, ['status_choices' => [2 => 'Gelöst']]);
            $invalidStatus->submit(['body' => 'Antwort', 'attachments' => [], 'targetStatus' => '999']);
            self::assertFalse($invalidStatus->isValid());
            $pdf = new UploadedFile($path, 'document.pdf', null, null, true);
            $bad = new UploadedFile($path, 'document.exe', null, null, true);
            foreach ([['', [], false], ['   ', [], false], ['Antwort', [], true], ['', [$pdf], true], ['Antwort', [$pdf], true], ['', [$bad], false]] as [$body, $attachments, $valid]) {
                $form = $factory->create(CommentType::class);
                $form->submit(['body' => $body, 'attachments' => $attachments]);
                self::assertSame($valid, $form->isValid(), (string) $form->getErrors(true));
            }
        } finally {
            unlink($path);
        }
    }
}
