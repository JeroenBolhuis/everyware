<?php

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
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

    <flux:heading class="sr-only">{{ __('Users') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Users')"
        :subheading="__('Create accounts and assign roles. Self-registration is disabled.')"
    >
        <div class="my-6 flex flex-col gap-4">
            <div class="flex justify-end">
                <flux:button variant="primary" :href="route('admin.users.create')" icon="plus" wire:navigate>
                    {{ __('Add user') }}
                </flux:button>
            </div>

            <flux:table :paginate="$this->users">
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">
                                    {{ $user->getRoleNames()->first() ?? '—' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    :href="route('admin.users.edit', $user)"
                                    icon="pencil-square"
                                    wire:navigate
                                >
                                    {{ __('Edit') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-pages::admin.layout>
</section>
