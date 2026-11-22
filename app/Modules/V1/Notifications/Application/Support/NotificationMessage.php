<?php

namespace App\Modules\V1\Notifications\Application\Support;

/**
 * Notifications store translation keys and params instead of text, so the same record is shown
 * in the reader's language. This renders the stored payload for one locale.
 */
class NotificationMessage
{
    public const SUPPORTED_LOCALES = ['ar', 'en'];

    public const DEFAULT_LOCALE = 'ar';

    /** @return array{title: ?string, body: ?string} */
    public static function render(array $data, ?string $locale = null): array
    {
        $locale = in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : app()->getLocale();
        $params = $data['params'] ?? [];

        foreach ($data['translated_params'] ?? [] as $name => $key) {
            $params[$name] = __($key, [], $locale);
        }

        return [
            // `title` / `body` hold translation keys; a plain text value is returned unchanged by __().
            'title' => isset($data['title']) ? __($data['title'], $params, $locale) : null,
            'body' => isset($data['body']) ? __($data['body'], $params, $locale) : null,
        ];
    }

    public static function localeFor(object $notifiable): string
    {
        $locale = $notifiable->locale ?? null;

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : self::DEFAULT_LOCALE;
    }
}
