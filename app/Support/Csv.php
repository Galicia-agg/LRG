<?php

namespace App\Support;

class Csv
{
    /**
     * Neutralizes formula-injection payloads (=, +, -, @, tab, CR) in a value
     * before it's written to a CSV cell, so spreadsheet apps don't execute it.
     */
    public static function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    public static function safeRow(array $row): array
    {
        return array_map([self::class, 'safeCell'], $row);
    }
}
