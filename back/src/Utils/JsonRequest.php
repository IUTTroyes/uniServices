<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;

class JsonRequest
{
    protected static array $data = [];

    public static function getValuesFromString(
        string $content = null
        ): Response|array
    {
        return self::getDataFromContent($content) ? self::$data : [];
    }

    public static function get(string $content, string $key): mixed
    {
       return self::getDataFromContent($content) ? self::$data[$key] ?? null : null;
    }

    public static function has(string $key): bool
    {
        return isset(self::$data[$key]);
    }

    private static function getDataFromContent(string $content): bool
    {
        if ($content === null) {
            throw new \InvalidArgumentException('Content is null');
        }

        self::$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR)
            ?? [];

        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$data = [];
            throw new \InvalidArgumentException('Invalid JSON');
        }

        return true;
    }
}
