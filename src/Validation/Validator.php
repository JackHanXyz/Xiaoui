<?php

declare(strict_types=1);

namespace Xiaoui\Validation;

/**
 * A small, rule-based input validator.
 */
class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function passes(): bool
    {
        return $this->errors() === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return array<string, list<string>> field => list of error messages
     */
    public function errors(): array
    {
        $errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                $error = $this->evaluate($field, $value, $rule);

                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    private function evaluate(string $field, mixed $value, string $rule): ?string
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($name) {
            'required' => $this->validateRequired($field, $value),
            'string' => is_string($value) ? null : "{$field} must be a string.",
            'integer' => filter_var($value, FILTER_VALIDATE_INT) === false
                ? "{$field} must be an integer."
                : null,
            'numeric' => is_numeric($value) ? null : "{$field} must be numeric.",
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? null
                : "{$field} must be a valid email.",
            'min' => is_numeric($value) && $value >= (int) $param
                ? null
                : "{$field} must be at least {$param}.",
            'max' => is_numeric($value) && $value <= (int) $param
                ? null
                : "{$field} must be at most {$param}.",
            'in' => $this->validateIn($field, $value, $param),
            'nullable' => null,
            default => null,
        };
    }

    private function validateRequired(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return "{$field} is required.";
        }

        return null;
    }

    private function validateIn(string $field, mixed $value, ?string $param): ?string
    {
        $allowed = explode(',', (string) $param);

        return in_array($value, $allowed, true)
            ? null
            : "{$field} must be one of: {$param}.";
    }
}
