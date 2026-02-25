<?php

declare(strict_types=1);

namespace App;

class Validation
{
    private array $errors = [];

    public function required(array $data, array $fields): self
    {
        foreach ($fields as $field) {
            $val = $data[$field] ?? null;
            if ($val === null || $val === '') {
                $this->errors[$field] = 'Поле обязательно для заполнения';
            }
        }
        return $this;
    }

    public function email(array $data, string $field): self
    {
        $val = trim($data[$field] ?? '');
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Некорректный email';
        }
        return $this;
    }

    public function minLength(array $data, string $field, int $min, ?string $label = null): self
    {
        $val = $data[$field] ?? '';
        if ($val !== '' && mb_strlen((string) $val) < $min) {
            $this->errors[$field] = ($label ?? 'Поле') . " не менее {$min} символов";
        }
        return $this;
    }

    public function equals(array $data, string $field1, string $field2, string $message = 'Значения не совпадают'): self
    {
        $v1 = $data[$field1] ?? '';
        $v2 = $data[$field2] ?? '';
        if ($v1 !== '' && $v1 !== $v2) {
            $this->errors[$field2] = $message;
        }
        return $this;
    }

    public function integer(array $data, string $field, int $min = 0, ?int $max = null): self
    {
        $val = $data[$field] ?? '';
        if ($val === '') return $this;
        $n = (int) $val;
        if ((string) $n !== (string) $val || $n < $min) {
            $this->errors[$field] = 'Введите целое число';
            return $this;
        }
        if ($max !== null && $n > $max) {
            $this->errors[$field] = "Значение не более {$max}";
        }
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[array_key_first($this->errors)] ?? null;
    }
}
