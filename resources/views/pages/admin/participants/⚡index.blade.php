<?php

use App\Models\Participant;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Deelnemers')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Participant::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getParticipantsProperty()
    {
        return Participant::query()
            ->when($this->search, fn ($query) => $query
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
            )
            ->orderBy('name')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Deelnemers') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Deelnemers')"
        :subheading="__('Bekijk deelnemers, hun puntensaldo en maak correcties.')"
    >
        <div class="my-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Zoek op naam of e-mail...') }}"
                    icon="magnifying-glass"
                    clearable
                />
            </div>

            <flux:table :paginate="$this->participants">
                <flux:table.columns>
                    <flux:table.column>{{ __('Naam') }}</flux:table.column>
                    <flux:table.column>{{ __('E-mail') }}</flux:table.column>
                    <flux:table.column>{{ __('Punten') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Acties') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->participants as $participant)
                        <flux:table.row :key="$participant->id">
                            <flux:table.cell variant="strong">
                                {{ $participant->name ?: '—' }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $participant->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $participant->current_points > 0 ? 'emerald' : 'zinc' }}" size="sm">
                                    {{ $participant->current_points }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($participant->isBlocked())
                                    <flux:badge color="red" size="sm">{{ __('Geblokkeerd') }}</flux:badge>
                                @else
                                    <flux:badge color="emerald" size="sm">{{ __('Actief') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <a href="{{ route('admin.participants.show', $participant) }}" class="btn-secondary" wire:navigate>
                                    {{ __('Bekijken') }}
                                </a>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <flux:text class="text-center text-zinc-500">
                                    {{ $search ? __('Geen deelnemers gevonden voor ":search".', ['search' => $search]) : __('Er zijn nog geen deelnemers.') }}
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-pages::admin.layout>
</section>
