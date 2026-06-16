<?php

use App\Actions\Participants\DeductParticipantPoints;
use App\Models\Participant;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Deelnemer')] class extends Component {
    public Participant $participant;

    #[Validate('required|integer|min:1', message: [
        'required' => 'Vul het aantal punten in.',
        'integer'  => 'Het aantal punten moet een heel getal zijn.',
        'min'      => 'Het aantal punten moet minimaal 1 zijn.',
    ])]
    public ?int $pointsToDeduct = null;

    #[Validate('required|string|max:255', message: [
        'required' => 'Geef een reden op voor de correctie.',
        'max'      => 'De reden mag maximaal 255 tekens bevatten.',
    ])]
    public string $reason = '';

    public function mount(): void
    {
        $this->authorize('view', $this->participant);
    }

    public function deductPoints(DeductParticipantPoints $deductParticipantPoints): void
    {
        $this->authorize('correctPoints', $this->participant);

        $this->validate();

        if ($this->pointsToDeduct > $this->participant->current_points) {
            $this->addError('pointsToDeduct', __('Je kunt niet meer punten afboeken dan de deelnemer heeft.'));

            return;
        }

        $deductParticipantPoints($this->participant, $this->pointsToDeduct, $this->reason);

        $this->reset('pointsToDeduct', 'reason');
        $this->participant->refresh();

        Session::flash('status', __('Punten succesvol afgeboekt.'));
    }
}; ?>

@php
    $canViewParticipantDetails = auth()->user()?->isAdmin() === true;
@endphp

<section class="w-full" aria-labelledby="admin-participant-show-page-title">
    <x-pages::admin.layout
        :heading="$participant->displayNameFor(auth()->user())"
        :subheading="__('Puntenhistorie en puntenaftrek voor deze deelnemer.')"
        heading-id="admin-participant-show-page-title"
    >
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200" role="status" aria-live="polite">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Pseudoniem') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->displayNameFor(auth()->user()) }}</flux:heading>
                </div>
                @if ($canViewParticipantDetails)
                    <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('E-mail') }}</flux:text>
                        <flux:heading class="mt-1">{{ $participant->displayEmailFor(auth()->user()) }}</flux:heading>
                    </div>
                @endif
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Huidige punten') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->current_points }}</flux:heading>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Punten afboeken') }}</flux:heading>
                <flux:subheading class="mt-1">
                    {{ __('Trek punten af wanneer een deelnemer deze extern inlevert voor een beloning.') }}
                </flux:subheading>

                <form wire:submit="deductPoints" class="mt-6 space-y-4 max-w-lg" aria-label="{{ __('Punten afboeken voor :name', ['name' => $participant->displayNameFor(auth()->user())]) }}">
                    <flux:field>
                        <flux:label>{{ __('Aantal punten') }}</flux:label>
                        <flux:description>{{ __('Vul het positieve aantal punten in dat van het saldo wordt afgehaald.') }}</flux:description>
                        <flux:input
                            wire:model="pointsToDeduct"
                            type="number"
                            min="1"
                            placeholder="{{ __('Bijv. 10') }}"
                        />
                        <flux:error name="pointsToDeduct" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Reden') }}</flux:label>
                        <flux:description>{{ __('Omschrijf welke externe beloning of afspraak hierbij hoort.') }}</flux:description>
                        <flux:textarea
                            wire:model="reason"
                            placeholder="{{ __('Bijv. Bol.com cadeaubon ingeleverd') }}"
                            rows="3"
                        />
                        <flux:error name="reason" />
                    </flux:field>

                    <div class="flex items-center gap-3">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="deductPoints">
                            {{ __('Punten afboeken') }}
                        </flux:button>
                        <flux:text class="text-xs text-zinc-500" wire:loading wire:target="deductPoints">
                            {{ __('Bezig...') }}
                        </flux:text>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Puntenhistorie') }}</flux:heading>

                <div class="mt-4">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Datum') }}</flux:table.column>
                            <flux:table.column>{{ __('Bedrag') }}</flux:table.column>
                            <flux:table.column>{{ __('Bron') }}</flux:table.column>
                            <flux:table.column>{{ __('Reden') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($participant->pointsHistories()->latest()->get() as $history)
                                <flux:table.row :key="$history->id">
                                    <flux:table.cell>
                                        {{ $history->created_at->format('d-m-Y H:i') }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge
                                            color="{{ $history->amount >= 0 ? 'emerald' : 'red' }}"
                                            size="sm"
                                        >
                                            {{ $history->amount >= 0 ? '+' : '' }}{{ $history->amount }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($history->source_type === null)
                                            <flux:badge color="amber" size="sm">{{ __('Handmatige mutatie') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('Enquete-inzending #:id', ['id' => $history->source_id]) }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $history->reason ?: '—' }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="4">
                                        <flux:text class="text-center text-zinc-500">
                                            {{ __('Nog geen puntenhistorie voor deze deelnemer.') }}
                                        </flux:text>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            <div>
                <flux:button
                    variant="ghost"
                    icon="arrow-left"
                    :href="route('admin.participants.index')"
                    wire:navigate
                >
                    {{ __('Terug naar deelnemers') }}
                </flux:button>
            </div>

        </div>
    </x-pages::admin.layout>
</section>
