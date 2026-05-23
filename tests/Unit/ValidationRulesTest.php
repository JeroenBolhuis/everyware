<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('exposes password validation rule sets', function () {
    $rules = new class
    {
        use PasswordValidationRules;

        public function password(): array
        {
            return $this->passwordRules();
        }

        public function currentPassword(): array
        {
            return $this->currentPasswordRules();
        }

        public function optionalPassword(): array
        {
            return $this->optionalPasswordRules();
        }
    };

    expect($rules->password())->toContain('required', 'string', 'confirmed')
        ->and($rules->password()[2])->toBeInstanceOf(Password::class)
        ->and($rules->currentPassword())->toBe(['required', 'string', 'current_password'])
        ->and($rules->optionalPassword())->toContain('nullable', 'string', 'confirmed')
        ->and($rules->optionalPassword()[2])->toBeInstanceOf(Password::class);
});

it('exposes profile validation rule sets', function () {
    $rules = new class
    {
        use ProfileValidationRules;

        public function profile(?int $userId = null): array
        {
            return $this->profileRules($userId);
        }

        public function name(): array
        {
            return $this->nameRules();
        }

        public function email(?int $userId = null): array
        {
            return $this->emailRules($userId);
        }
    };

    expect($rules->name())->toBe(['required', 'string', 'max:255'])
        ->and($rules->email()[4])->toBeInstanceOf(Unique::class)
        ->and($rules->email(42)[4])->toBeInstanceOf(Unique::class)
        ->and($rules->profile(42))->toHaveKeys(['name', 'email'])
        ->and($rules->profile(42)['name'])->toBe(['required', 'string', 'max:255']);
});
