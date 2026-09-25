<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage;
use PHPUnit\Framework\TestCase;

final class AttachmentStorageTest extends TestCase
{
    public function testInvalidFilesStorageKeyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new LocalAttachmentStorage('/private/attachments'))->path('files:../../outside/file.pdf');
    }

    public function testModuleUsesFolderPicker(): void
    {
        require dirname(__DIR__, 3).'/contao/dca/tl_module.php';
        $field = $GLOBALS['TL_DCA']['tl_module']['fields']['issue_attachment_folder'];
        self::assertSame('fileTree', $field['inputType']);
        self::assertFalse($field['eval']['files']);
        self::assertSame('binary(16) NULL', $field['sql']);
    }

    public function testExistingAndConfiguredStorageKeysRemainResolvable(): void
    {
        $storage = new LocalAttachmentStorage('/private/attachments');
        $filename = str_repeat('a', 32).'.pdf';
        self::assertSame('/private/attachments/aa/'.$filename, $storage->path($filename));
        self::assertSame('/private/attachments/support/documents/aa/'.$filename, $storage->path('support/documents/'.$filename));
        foreach (['../outside', '/tmp', 'folder/../outside', 'folder//bad', 'folder\\bad'] as $directory) {
            try {
                LocalAttachmentStorage::validateDirectory($directory);
                self::fail('Invalid directory accepted: '.$directory);
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
