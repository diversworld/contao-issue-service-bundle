<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\AttachmentResponse;
use PHPUnit\Framework\TestCase;

final class AttachmentResponseTest extends TestCase
{
    public function testPreviewAndDownloadHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'preview-');
        try {
            foreach ([['application/pdf', true, 'inline'], ['image/png', true, 'inline'], ['text/html', true, 'attachment'], ['image/svg+xml', true, 'attachment'], ['application/pdf', false, 'attachment']] as [$mime, $preview, $disposition]) {
                $response = AttachmentResponse::create($path, 'document.pdf', $mime, $preview);
                self::assertStringStartsWith($disposition.';', $response->headers->get('Content-Disposition'));
                self::assertSame($disposition === 'inline' ? $mime : 'application/octet-stream', $response->headers->get('Content-Type'));
                self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
            }
        } finally {
            unlink($path);
        }
    }
}
