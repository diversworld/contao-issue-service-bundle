<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\{AttachmentConstraints, AttachmentService, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage;
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class AttachmentServiceTest extends TestCase
{
    public function testMetadataIsReadBeforeMovingUpload(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'issue-upload-');
        $directory = sys_get_temp_dir().'/issue-storage-'.bin2hex(random_bytes(8));
        $body = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF";
        file_put_contents($source, $body);
        $storage = new LocalAttachmentStorage($directory);
        $stored = null;
        $db = $this->createMock(Connection::class);
        $db->method('fetchOne')->willReturnCallback(static fn ($sql, $params) => isset($params['k']) ? false : 0);
        $db->method('lastInsertId')->willReturn('42');
        $db->expects(self::once())->method('insert')->willReturnCallback(function ($table, $data) use (&$stored, $body): int {
            $stored = $data;
            self::assertSame('tl_issue_attachment', $table);
            self::assertSame('application/pdf', $data['mime_type']);
            self::assertSame(strlen($body), $data['file_size']);
            self::assertSame(hash('sha256', $body), $data['sha256']);
            self::assertSame('document.pdf', $data['original_name']);
            return 1;
        });
        $settings = new SettingsService(new SettingsRepository($db));
        $service = new AttachmentService($db, $settings, $storage, new AttachmentConstraints($settings), Validation::createValidator());
        try {
            self::assertSame(42, $service->upload(123, new UploadedFile($source, 'document.pdf', null, null, true), 'member', 7));
            self::assertFileDoesNotExist($source);
            self::assertSame($body, file_get_contents($storage->path($stored['storage_key'])));
        } finally {
            if (is_file($source)) unlink($source);
            foreach (glob($directory.'/*/*') ?: [] as $file) unlink($file);
            foreach (glob($directory.'/*') ?: [] as $folder) rmdir($folder);
            if (is_dir($directory)) rmdir($directory);
        }
    }
}
