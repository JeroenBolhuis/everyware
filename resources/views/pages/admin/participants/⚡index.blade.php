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
                ->where('email', 'like', '%' . $this->search . '%')
            )
            ->orderBy('email')
            ->paginate(15);
    }
}; ?>

<section class="w-full" aria-labelledby="admin-participants-page-title">
    @include('partials.admin-heading')

    <flux:heading class="sr-only" id="admin-participants-page-title">{{ __('Deelnemers') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Deelnemers')"
        :subheading="__('Bekijk deelnemers, hun puntensaldo en maak correcties.')"
    >
        <div class="my-6 rounded-lg sm:rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Zoek op e-mail...') }}"
                    icon="magnifying-glass"
                    clearable
                    class="w-full text-xs sm:text-sm"
                    aria-label="{{ __('Zoek deelnemers op naam of e-mail') }}"
                />
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->participants">
                    <flux:table.columns>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('E-mail') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Punten') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="text-xs sm:text-sm">{{ __('Acties') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->participants as $participant)
                            <flux:table.row :key="$participant->id">
                                <flux:table.cell variant="strong" class="text-xs sm:text-sm truncate">{{ $participant->email }}</flux:table.cell>
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
                                    <a href="{{ route('admin.participants.show', $participant) }}" class="btn-secondary text-xs sm:text-sm whitespace-nowrap" wire:navigate aria-label="{{ __('Bekijk deelnemer :name', ['name' => $participant->name ?: $participant->email]) }}">
                                        {{ __('Bekijken') }}
                                    </a>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">
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
