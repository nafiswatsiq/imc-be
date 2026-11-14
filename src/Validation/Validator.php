<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validatable;

final class Validator
{
    /**
     * Validates $data against field => rule pairs and returns only the validated fields.
     * With $partial, fields absent from $data are skipped (used for updates).
     *
     * @param array<string, mixed> $data
     * Null values are dropped from the result unless the field is listed in $nullable.
     *
     * @param array<string, Validatable> $rules
     * @param list<string> $nullable
     * @return array<string, mixed>
     */
    public static function validate(array $data, array $rules, bool $partial = false, array $nullable = []): array
    {
        $errors = [];
        $clean = [];

        foreach ($rules as $field => $rule) {
            if ($partial && !array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field] ?? null;

            try {
                $rule->setName(self::label($field))->assert($value);
                if (array_key_exists($field, $data) && ($value !== null || in_array($field, $nullable, true))) {
                    $clean[$field] = $value;
                }
            } catch (NestedValidationException $e) {
                $errors[$field] = array_values($e->getMessages());
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $clean;
    }

    private static function label(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
