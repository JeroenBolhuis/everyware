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
        $search = trim($this->search);

        return Participant::query()
            ->when($search !== '', fn ($query) => $query
                ->where('id', ltrim(str_replace(['#', ' '], '', $search), '0') ?: 0)
            )
            ->orderBy('id')
            ->paginate(15);
    }
}; ?>

@php
    $canViewParticipantDetails = auth()->user()?->isAdmin() === true;
@endphp

<section class="w-full" aria-labelledby="admin-participants-page-title">
    <x-pages::admin.layout
        :heading="__('Deelnemers')"
        :subheading="__('Bekijk deelnemers, hun puntensaldo en boek punten af.')"
        heading-id="admin-participants-page-title"
    >
        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Zoek op #...') }}"
                    icon="magnifying-glass"
                    clearable
                    class="w-full text-xs sm:text-sm"
                    aria-label="{{ __('Zoek deelnemers op pseudoniem') }}"
                />
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->participants">
                    <flux:table.columns>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Pseudoniem') }}</flux:table.column>
                        @if ($canViewParticipantDetails)
                            <flux:table.column class="text-xs sm:text-sm">{{ __('E-mail') }}</flux:table.column>
                        @endif
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Punten') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="text-xs sm:text-sm">{{ __('Acties') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->participants as $participant)
                            <flux:table.row :key="$participant->id">
                                <flux:table.cell variant="strong" class="text-xs sm:text-sm">
                                    {{ $participant->displayNameFor(auth()->user()) }}
                                </flux:table.cell>
                                @if ($canViewParticipantDetails)
                                    <flux:table.cell class="text-xs sm:text-sm truncate">{{ $participant->displayEmailFor(auth()->user()) }}</flux:table.cell>
                                @endif
                                <flux:table.cell class="text-xs sm:text-sm">
                                    <flux:badge color="{{ $participant->current_points > 0 ? 'emerald' : 'zinc' }}" size="sm">
                                        {{ $participant->current_points }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm">
                                    @if ($participant->isBlocked())
                                        <flux:badge color="red" size="sm">{{ __('Geblokkeerd') }}</flux:badge>
                                    @else
                                        <flux:badge color="emerald" size="sm">{{ __('Actief') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="eye"
                                        :href="route('admin.participants.show', $participant)"
                                        wire:navigate
                                        aria-label="{{ __('Bekijk deelnemer :name', ['name' => $participant->displayNameFor(auth()->user())]) }}"
                                    >
                                        {{ __('Bekijken') }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="{{ $canViewParticipantDetails ? 5 : 4 }}">
                                    <flux:text class="text-center text-xs sm:text-sm text-zinc-500">
                                        {{ $search ? __('Geen deelnemers gevonden voor ":search".', ['search' => $search]) : __('Er zijn nog geen deelnemers.') }}
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
