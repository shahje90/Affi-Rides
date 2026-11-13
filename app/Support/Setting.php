<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default): mixed {
            $value = DB::table('settings')->where('key', $key)->value('value');

            return $value ?? $default;
        });
    }

    public static function forget(string $key): void
    {
        Cache::forget("settings.{$key}");
    }
}
