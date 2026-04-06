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

    public array $roles = [];

    public function mount(): void
    {
        $this->authorize('create', User::class);
        $this->roles = [RoleEnum::User->value];
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::enum(RoleEnum::class)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['roles']);

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Gebruiker aanmaken') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Gebruiker aanmaken')"
        :subheading="__('Stel een wachtwoord in en kies een of meer rollen. De gebruiker kan direct inloggen.')"
    >
        <div class="my-6 w-full max-w-lg space-y-6">
            <div>
                <flux:button variant="ghost" :href="route('admin.users.index')" icon="arrow-left" wire:navigate>
                    {{ __('Terug naar gebruikers') }}
                </flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name" />

                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                <flux:input wire:model="password" :label="__('Wachtwoord')" type="password" viewable autocomplete="new-password" />

                <flux:input wire:model="password_confirmation" :label="__('Bevestig wachtwoord')" type="password" viewable autocomplete="new-password" />

                <flux:field>
                    <flux:label>{{ __('Rollen') }}</flux:label>

                    <div class="mt-3 space-y-3">
                        @foreach (RoleEnum::cases() as $roleOption)
                            <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                <input type="checkbox" wire:model="roles" value="{{ $roleOption->value }}" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" />
                                <span>{{ $roleOption->label() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <flux:error name="roles" />
                    <flux:error name="roles.*" />
                </flux:field>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit">{{ __('Gebruiker aanmaken') }}</flux:button>
                </div>
            </form>
        </div>
    </x-pages::admin.layout>
</section>
