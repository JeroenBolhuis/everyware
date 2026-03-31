<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create user')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public function mount(): void
    {
        $this->authorize('create', User::class);
        $this->role = RoleEnum::User->value;
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::enum(RoleEnum::class)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Create user') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Create user')"
        :subheading="__('Set a password and choose a role. The user can sign in immediately.')"
    >
        <div class="my-6 w-full max-w-lg space-y-6">
            <div>
                <flux:button variant="ghost" :href="route('admin.users.index')" icon="arrow-left" wire:navigate>
                    {{ __('Back to users') }}
                </flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name" />

                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                <flux:input wire:model="password" :label="__('Password')" type="password" viewable autocomplete="new-password" />

                <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" viewable autocomplete="new-password" />

                <flux:select wire:model="role" :label="__('Role')">
                    @foreach (RoleEnum::cases() as $roleOption)
                        <flux:select.option :value="$roleOption->value">{{ $roleOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit">{{ __('Create user') }}</flux:button>
                </div>
            </form>
        </div>
    </x-pages::admin.layout>
</section>
