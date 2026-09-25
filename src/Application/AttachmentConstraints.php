<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;

use Symfony\Component\Validator\Constraints as Assert;

final class AttachmentConstraints
{
    public function __construct(private readonly SettingsService $settings) {}

    public function forProfile(int $id): self { return new self($this->settings->forProfile($id)); }

    public function extensions(): array
    {
        return array_map('strtolower', SettingsList::decode($this->settings->json('allowed_extensions', ['pdf', 'png', 'jpg', 'jpeg'])));
    }

    public function file(): Assert\File
    {
        return new Assert\File(
            maxSize: $this->settings->int('max_file_size', 10485760),
            extensions: $this->extensions() ?: ['__disabled__'],
            extensionsMessage: 'Dieser Dateityp ist nicht erlaubt. Erlaubt: {{ extensions }}.',
            mimeTypesMessage: 'Der Dateiinhalt passt nicht zum erlaubten Dateityp.',
            maxSizeMessage: 'Die Datei ist zu groß. Maximal erlaubt: {{ limit }} {{ suffix }}.',
        );
    }

    public function all(): array
    {
        return [new Assert\Count(max: $this->settings->int('max_files_per_issue', 5), maxMessage: 'Es sind höchstens {{ limit }} Anhänge erlaubt.'), new Assert\All([$this->file()])];
    }
}
