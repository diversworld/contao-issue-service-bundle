<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\{AttachmentConstraints, SettingsList, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class AttachmentConstraintsTest extends TestCase
{
    public function testSerializedCheckboxValuesAndExistingMalformedJson(): void
    {
        $values = ['pdf', 'png', 'jpg', 'jpeg'];
        self::assertSame($values, SettingsList::decode(serialize($values)));
        self::assertSame($values, SettingsList::decode(json_encode([serialize($values)])));
        self::assertSame($values, SettingsList::decode(json_encode($values)));
    }

    public function testValidFilesAndInvalidUploads(): void
    {
        $db = $this->createStub(Connection::class);
        $db->method('fetchOne')->willReturnCallback(static fn ($sql, $params) => match ($params['k']) {
            'allowed_extensions' => json_encode([serialize(['pdf'])]),
            'max_file_size' => '100',
            'max_files_per_issue' => '1',
            default => false,
        });
        $rules = new AttachmentConstraints(new SettingsService(new SettingsRepository($db)));
        $validator = Validation::createValidator();
        $path = tempnam(sys_get_temp_dir(), 'issue-upload-');
        try {
            file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
            $pdf = new UploadedFile($path, 'document.pdf', null, null, true);
            self::assertCount(0, $validator->validate([$pdf], $rules->all()));
            self::assertGreaterThan(0, count($validator->validate([$pdf, $pdf], $rules->all())));
            self::assertGreaterThan(0, count($validator->validate([new UploadedFile($path, 'document.exe', null, null, true)], $rules->all())));
            file_put_contents($path, 'This is plain text, not a PDF.');
            self::assertGreaterThan(0, count($validator->validate([$pdf], $rules->all())));
            file_put_contents($path, "%PDF-1.4\n".str_repeat('x', 200));
            clearstatcache(true, $path);
            self::assertGreaterThan(0, count($validator->validate([$pdf], $rules->all())));
        } finally {
            unlink($path);
        }
    }
}
