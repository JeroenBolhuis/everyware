<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gebruiker aanmaken')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = RoleEnum::LicEmployee->value;

    public function mount(): void
    {
        $this->authorize('create', User::class);
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
            'role' => $validated['role'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<section class="w-full" aria-labelledby="admin-users-create-page-title">
    @include('partials.admin-heading')

    <flux:heading class="sr-only" id="admin-users-create-page-title">{{ __('Gebruiker aanmaken') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Gebruiker aanmaken')"
        :subheading="__('Stel een wachtwoord in en kies een rol. De gebruiker kan direct inloggen.')"
    >
        <div
            class="my-6 w-full max-w-5xl space-y-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary" wire:navigate>
                    {{ __('Terug naar gebruikers') }}
                </a>
            </div>

            <form wire:submit="save" class="space-y-6" aria-label="{{ __('Nieuw gebruikersaccount aanmaken') }}">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name"/>

                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email"/>

                <flux:input wire:model="password" :label="__('Wachtwoord')" type="password" viewable
                            autocomplete="new-password"/>

                <flux:input wire:model="password_confirmation" :label="__('Bevestig wachtwoord')" type="password"
                            viewable autocomplete="new-password"/>

                <flux:field>
                    <flux:label>{{ __('Rol') }}</flux:label>

                    @include('pages.admin.users._role-cards', ['role' => $role])

                    <flux:error name="role"/>
                </flux:field>

                <div class="flex items-center gap-4">
                    <button type="submit" class="btn-primary">{{ __('Gebruiker aanmaken') }}</button>
                </div>
            </form>
        </div>
    </x-pages::admin.layout>
</section>
