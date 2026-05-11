<?php
declare(strict_types=1);

function env(string $key, string $default = ''): string
{
    $val = getenv($key);

    if ($val === false || $val === '') {
        return $default;
    }

    return $val;
}