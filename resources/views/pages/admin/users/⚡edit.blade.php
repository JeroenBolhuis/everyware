<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gebruiker bewerken')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public array $roles = [];

    public function mount(): void
    {
        $this->authorize('update', $this->user);
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->roles = $this->user->getRoleNames()->values()->all();

        if ($this->roles === []) {
            $this->roles = [RoleEnum::User->value];
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate([
            ...$this->profileRules($this->user->id),
            'password' => $this->optionalPasswordRules(),
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::enum(RoleEnum::class)],
        ]);

        if (
            $this->user->hasRole(RoleEnum::Admin->value)
            && !in_array(RoleEnum::Admin->value, $validated['roles'], true)
            && User::role(RoleEnum::Admin->value)->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'roles' => __('Er moet minimaal een beheerder zijn.'),
            ]);
        }

        $this->user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        if (!empty($validated['password'])) {
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        $this->user->syncRoles($validated['roles']);

        $this->dispatch('user-saved');
    }

    public function deleteUser(): void
    {
        $this->authorize('delete', $this->user);

        $this->user->delete();

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Gebruiker bewerken') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Gebruiker bewerken')"
        :subheading="__('Werk accountgegevens, rollen of wachtwoord bij.')"
    >
        <div
            class="my-6 w-full max-w-lg space-y-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary" wire:navigate>
                    {{ __('Terug naar gebruikers') }}
                </a>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name"/>

                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email"/>

                <flux:separator/>

                <flux:heading size="lg">{{ __('Wachtwoord wijzigen') }}</flux:heading>
                <flux:text
                    class="text-sm text-zinc-500">{{ __('Laat leeg om het huidige wachtwoord te behouden.') }}</flux:text>

                <flux:input wire:model="password" :label="__('Nieuw wachtwoord')" type="password" viewable
                            autocomplete="new-password"/>

                <flux:input wire:model="password_confirmation" :label="__('Bevestig nieuw wachtwoord')" type="password"
                            viewable autocomplete="new-password"/>

                <flux:field>
                    <flux:label>{{ __('Rollen') }}</flux:label>

                    <div class="mt-3 space-y-3">
                        @foreach (RoleEnum::cases() as $roleOption)
                            <label
                                class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                <input type="checkbox" wire:model="roles" value="{{ $roleOption->value }}"
                                       class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500"/>
                                <span>{{ $roleOption->label() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <flux:error name="roles"/>
                    <flux:error name="roles.*"/>
                </flux:field>

                <div class="flex flex-wrap items-center gap-4">
                    <button type="submit" class="btn-primary">{{ __('Wijzigingen opslaan') }}</button>

                    <x-action-message on="user-saved">
                        {{ __('Opgeslagen.') }}
                    </x-action-message>
                </div>
            </form>

            @if ($this->user->isNot(auth()->user()))
                <flux:separator class="my-8"/>

                <div class="space-y-3">
                    <flux:heading size="lg">{{ __('Gebruiker verwijderen') }}</flux:heading>
                    <flux:text
                        class="text-sm text-zinc-500">{{ __('Dit verwijdert het account definitief.') }}</flux:text>
                    <flux:error name="delete"/>
                    <button
                        type="button"
                        class="btn-primary"
                        wire:click="deleteUser"
                        wire:confirm="{{ __('Weet je zeker dat je deze gebruiker wilt verwijderen?') }}"
                    >
                        {{ __('Gebruiker verwijderen') }}
                    </button>
                </div>
            @endif
        </div>
    </x-pages::admin.layout>
</section>
