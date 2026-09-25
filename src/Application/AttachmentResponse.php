<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class AttachmentResponse
{
    public static function create(string $path, string $name, string $mime, bool $preview): BinaryFileResponse
    {
        $inline = $preview && in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition($inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name);
        $response->headers->set('Content-Type', $inline ? $mime : 'application/octet-stream');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "sandbox; default-src 'none'");
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
