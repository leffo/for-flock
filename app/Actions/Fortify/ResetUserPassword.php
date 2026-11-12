<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function reset(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->update($validated);
    }
}
