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
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Gebruikers') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Gebruikers')"
        :subheading="__('Maak accounts aan en wijs rollen toe. Zelfregistratie staat uit.')"
    >
        <div
            class="my-6 flex flex-col gap-4 rounded-lg sm:rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-0">
                <a href="{{ route('admin.users.create') }}" class="btn-primary w-full sm:w-auto text-center" wire:navigate>
                    {{ __('Gebruiker toevoegen') }}
                </a>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->users">
                    <flux:table.columns>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Name') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Email') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Rollen') }}</flux:table.column>
                        <flux:table.column align="end" class="text-xs sm:text-sm">{{ __('Acties') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell variant="strong" class="text-xs sm:text-sm">{{ $user->name }}</flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm truncate">{{ $user->email }}</flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm">
                                    <div class="flex flex-wrap gap-1 sm:gap-2">
                                        @forelse ($user->getRoleNames() as $roleName)
                                            <flux:badge color="zinc"
                                                        size="sm">{{ RoleEnum::tryFrom($roleName)?->label() ?? $roleName }}</flux:badge>
                                        @empty
                                            <span class="text-xs">&mdash;</span>
                                        @endforelse
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary text-xs sm:text-sm whitespace-nowrap" wire:navigate>
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
