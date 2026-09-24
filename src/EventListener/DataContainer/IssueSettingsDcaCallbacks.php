<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;

final class IssueSettingsDcaCallbacks
{
    public const SETTING_KEYS = [
        'require_login',
        'ticket_pattern',
        'allowed_extensions',
        'max_file_size',
        'max_files_per_issue',
        'retention_policy',
        'reopen_roles',
        'mail_recipients',
        'service_scoped_permissions',
    ];

    private const VALUE_TYPES = [
        'require_login' => 'bool',
        'ticket_pattern' => 'string',
        'allowed_extensions' => 'json',
        'max_file_size' => 'int',
        'max_files_per_issue' => 'int',
        'retention_policy' => 'json',
        'reopen_roles' => 'json',
        'mail_recipients' => 'json',
        'service_scoped_permissions' => 'bool',
    ];

    public function configureValueField(DataContainer $dc): void
    {
        $key = $this->settingKey($dc);
        $field = &$GLOBALS['TL_DCA']['tl_issue_settings']['fields']['setting_value'];
        $field['label'] = $GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_'.$key] ?? $GLOBALS['TL_LANG']['tl_issue_settings']['setting_value'];

        match ($key) {
            'require_login', 'service_scoped_permissions' => $this->configureCheckbox($field),
            'allowed_extensions' => $this->configureAllowedExtensions($field),
            'max_file_size', 'max_files_per_issue', 'retention_policy' => $this->configurePositiveInteger($field),
            'reopen_roles' => $this->configureReopenRoles($field),
            'mail_recipients' => $this->configureMailRecipients($field),
            default => $this->configureText($field),
        };
    }

    /** @param array<string,mixed> $field */
    private function configureCheckbox(array &$field): void
    {
        $field['inputType'] = 'checkbox';
        $field['eval'] = ['tl_class' => 'clr w50'];
    }

    /** @param array<string,mixed> $field */
    private function configureAllowedExtensions(array &$field): void
    {
        $field['inputType'] = 'checkbox';
        $field['options'] = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
        $field['eval'] = ['multiple' => true, 'tl_class' => 'clr'];
    }

    /** @param array<string,mixed> $field */
    private function configurePositiveInteger(array &$field): void
    {
        $field['inputType'] = 'text';
        $field['eval'] = ['mandatory' => true, 'rgxp' => 'natural', 'tl_class' => 'clr w50'];
    }

    /** @param array<string,mixed> $field */
    private function configureReopenRoles(array &$field): void
    {
        $field['inputType'] = 'checkbox';
        $field['options'] = ['member', 'agent', 'manager'];
        $field['reference'] = &$GLOBALS['TL_LANG']['tl_issue_settings']['role_options'];
        $field['eval'] = ['multiple' => true, 'tl_class' => 'clr'];
    }

    /** @param array<string,mixed> $field */
    private function configureMailRecipients(array &$field): void
    {
        $field['inputType'] = 'textarea';
        $field['eval'] = ['decodeEntities' => true, 'tl_class' => 'clr long'];
    }

    /** @param array<string,mixed> $field */
    private function configureText(array &$field): void
    {
        $field['inputType'] = 'text';
        $field['eval'] = ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr w50'];
    }

    /** @return string|array<string> */
    public function loadValue(?string $value, DataContainer $dc): string|array
    {
        $key = $this->settingKey($dc);

        if (\in_array($key, ['allowed_extensions', 'reopen_roles'], true)) {
            return $this->decodeList($value);
        }

        if ('mail_recipients' === $key) {
            return implode("\n", $this->decodeList($value));
        }

        if ('retention_policy' === $key) {
            $policy = $this->decodeObject($value);

            return (string) ($policy['closed_days'] ?? 730);
        }

        return (string) $value;
    }

    /** @param string|array<string>|null $value */
    public function saveValue(string|array|null $value, DataContainer $dc): string
    {
        $key = $this->settingKey($dc);

        if (\in_array($key, ['require_login', 'service_scoped_permissions'], true)) {
            return $value ? '1' : '0';
        }

        if (\in_array($key, ['allowed_extensions', 'reopen_roles'], true)) {
            return json_encode(array_values((array) $value), JSON_THROW_ON_ERROR);
        }

        if ('mail_recipients' === $key) {
            $recipients = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) $value) ?: [])));

            return json_encode($recipients, JSON_THROW_ON_ERROR);
        }

        if ('retention_policy' === $key) {
            return json_encode(['closed_days' => max(1, (int) $value)], JSON_THROW_ON_ERROR);
        }

        return trim((string) $value);
    }

    public function updateMetadata(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $key = $this->settingKey($dc);
        $connection = $this->connection();
        $user = BackendUser::getInstance();

        $connection->update('tl_issue_settings', [
            'value_type' => self::VALUE_TYPES[$key] ?? 'string',
            'updated_by' => $user->id ?: null,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'version' => (int) $connection->fetchOne('SELECT version FROM tl_issue_settings WHERE id = :id', ['id' => $dc->id]) + 1,
        ], ['id' => $dc->id]);
    }

    /** @param array<string,mixed> $row */
    public function formatLabel(array $row): string
    {
        $key = (string) $row['setting_key'];
        $label = $GLOBALS['TL_LANG']['tl_issue_settings']['setting_key_options'][$key][0] ?? $key;
        $value = $this->formatValue($key, (string) ($row['setting_value'] ?? ''));

        if (\is_array($value)) {
            $value = implode(', ', $value);
        }

        return sprintf('%s <span style="color:#999">[%s]</span>', $label, $value);
    }

    private function formatValue(string $key, string $value): string|array
    {
        if (\in_array($key, ['allowed_extensions', 'reopen_roles'], true)) {
            return $this->decodeList($value);
        }

        if ('mail_recipients' === $key) {
            return $this->decodeList($value);
        }

        if ('retention_policy' === $key) {
            $policy = $this->decodeObject($value);

            return (string) ($policy['closed_days'] ?? 730);
        }

        return $value;
    }

    private function settingKey(DataContainer $dc): string
    {
        $postedKey = (string) Input::post('setting_key');

        if (\in_array($postedKey, self::SETTING_KEYS, true)) {
            return $postedKey;
        }

        $key = '';

        if ($dc->id) {
            $key = (string) $this->connection()->fetchOne('SELECT setting_key FROM tl_issue_settings WHERE id = :id', ['id' => $dc->id]);
        }

        return \in_array($key, self::SETTING_KEYS, true) ? $key : 'ticket_pattern';
    }

    /** @return list<string> */
    private function decodeList(?string $value): array
    {
        if (null === $value || '' === $value) {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    /** @return array<string,mixed> */
    private function decodeObject(?string $value): array
    {
        if (null === $value || '' === $value) {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }

    private function connection(): Connection
    {
        $connection = System::getContainer()->get('database_connection');
        \assert($connection instanceof Connection);

        return $connection;
    }
}