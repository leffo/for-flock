<?php

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * @return list<string|Password|Closure>
     */
    protected function passwordRules(): array
    {
        return [
            'bail',
            'required',
            'string',
            Password::default(),
            'confirmed',
            function (string $attribute, string $value, Closure $fail): void {
                if (strlen($value) > 72 || str_contains($value, "\0")) {
                    $fail('The password must be at most 72 bytes and may not contain null bytes.');
                }
            },
        ];
    }
}
