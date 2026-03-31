<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit user')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public function mount(): void
    {
        $this->authorize('update', $this->user);
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->role = $this->user->roles->first()?->name ?? RoleEnum::User->value;
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate([
            ...$this->profileRules($this->user->id),
            'password' => $this->optionalPasswordRules(),
            'role' => ['required', Rule::enum(RoleEnum::class)],
        ]);

        if (
            $this->user->hasRole(RoleEnum::Admin->value)
            && $validated['role'] !== RoleEnum::Admin->value
            && User::role(RoleEnum::Admin->value)->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => __('There must be at least one administrator.'),
            ]);
        }

        $this->user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        $this->user->syncRoles([$validated['role']]);

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

    <flux:heading class="sr-only">{{ __('Edit user') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Edit user')"
        :subheading="__('Update account details, role, or password.')"
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

                <flux:separator />

                <flux:heading size="lg">{{ __('Change password') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Leave blank to keep the current password.') }}</flux:text>

                <flux:input wire:model="password" :label="__('New password')" type="password" viewable autocomplete="new-password" />

                <flux:input wire:model="password_confirmation" :label="__('Confirm new password')" type="password" viewable autocomplete="new-password" />

                <flux:select wire:model="role" :label="__('Role')">
                    @foreach (RoleEnum::cases() as $roleOption)
                        <flux:select.option :value="$roleOption->value">{{ $roleOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:error name="role" />

                <div class="flex flex-wrap items-center gap-4">
                    <flux:button variant="primary" type="submit">{{ __('Save changes') }}</flux:button>

                    <x-action-message on="user-saved">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>

            @if ($this->user->isNot(auth()->user()))
                <flux:separator class="my-8" />

                <div class="space-y-3">
                    <flux:heading size="lg">{{ __('Delete user') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ __('This permanently removes the account.') }}</flux:text>
                    <flux:error name="delete" />
                    <flux:button
                        variant="danger"
                        type="button"
                        wire:click="deleteUser"
                        wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                    >
                        {{ __('Delete user') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </x-pages::admin.layout>
</section>
