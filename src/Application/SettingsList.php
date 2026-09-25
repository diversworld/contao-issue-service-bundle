<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;

final class SettingsList
{
    /** @return list<string> */
    public static function decode(string|array|null $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } elseif (str_starts_with($value, 'a:')) {
                $decoded = @unserialize($value, ['allowed_classes' => false]);
                $value = is_array($decoded) ? $decoded : [];
            } else {
                $value = $value === '' ? [] : [$value];
            }
        }
        $result = [];
        foreach ($value ?? [] as $entry) {
            if (is_string($entry) && str_starts_with($entry, 'a:')) {
                array_push($result, ...self::decode($entry));
            } elseif (is_string($entry) && trim($entry) !== '') {
                $result[] = trim($entry);
            }
        }
        return array_values(array_unique($result));
    }
}
