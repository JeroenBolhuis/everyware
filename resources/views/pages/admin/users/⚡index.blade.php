<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Gebruikers')] class extends Component {
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function getUsersProperty()
    {
        return User::query()
            ->orderBy('name')
            ->paginate(15);
    }
}; ?>

<section class="w-full" aria-labelledby="admin-users-page-title">
    <x-pages::admin.layout
        :heading="__('Gebruikers')"
        :subheading="__('Maak accounts aan en wijs rollen toe. Zelfregistratie staat uit.')"
        heading-id="admin-users-page-title"
    >
        @if (session('status'))
            <div
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200"
                role="status"
                aria-live="polite"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-0">
                <a href="{{ route('admin.users.create') }}" class="btn-primary w-full sm:w-auto text-center" wire:navigate aria-label="{{ __('Gebruiker toevoegen') }}">
                    {{ __('Gebruiker toevoegen') }}
                </a>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->users">
                    <flux:table.columns>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Name') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Email') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Rol') }}</flux:table.column>
                        <flux:table.column align="end" class="text-xs sm:text-sm">{{ __('Acties') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell variant="strong" class="text-xs sm:text-sm">{{ $user->name }}</flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm truncate">{{ $user->email }}</flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm">
                                    <flux:badge color="zinc" size="sm">{{ $user->role->label() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary text-xs sm:text-sm whitespace-nowrap" wire:navigate aria-label="{{ __('Bewerk gebruiker :name', ['name' => $user->name]) }}">
                                        {{ __('Bewerken') }}
                                    </a>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
