<?php

use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Deelnemer')] class extends Component {
    public Participant $participant;

    #[Validate('required|integer|not_in:0', message: [
        'required' => 'Vul een bedrag in.',
        'integer'  => 'Het bedrag moet een heel getal zijn.',
        'not_in'   => 'Het bedrag mag niet nul zijn.',
    ])]
    public ?int $amount = null;

    #[Validate('required|string|max:255', message: [
        'required' => 'Geef een reden op voor de correctie.',
        'max'      => 'De reden mag maximaal 255 tekens bevatten.',
    ])]
    public string $reason = '';

    public function mount(): void
    {
        $this->authorize('view', $this->participant);
    }

    public function addCorrection(): void
    {
        $this->authorize('correctPoints', $this->participant);

        $this->validate();

        DB::transaction(function (): void {
            ParticipantPointsHistory::create([
                'participant_id' => $this->participant->id,
                'amount'         => $this->amount,
                'reason'         => $this->reason,
                'source_type'    => null,
                'source_id'      => null,
            ]);

            $this->participant->increment('current_points', $this->amount);
        });

        $this->reset('amount', 'reason');
        $this->participant->refresh();

        Session::flash('status', __('Correctie succesvol opgeslagen.'));
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Deelnemer') }}</flux:heading>

    <x-pages::admin.layout
        :heading="$participant->name ?: $participant->email"
        :subheading="__('Puntenhistorie en correcties voor deze deelnemer.')"
    >
        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="my-6 space-y-6">

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Naam') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->name ?: '—' }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('E-mail') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->email }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Huidige punten') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->current_points }}</flux:heading>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Correctie toevoegen') }}</flux:heading>
                <flux:subheading class="mt-1">
                    {{ __('Voeg een positieve of negatieve correctie toe aan het puntensaldo van deze deelnemer.') }}
                </flux:subheading>

                <form wire:submit="addCorrection" class="mt-6 space-y-4 max-w-lg">
                    <flux:field>
                        <flux:label>{{ __('Bedrag') }}</flux:label>
                        <flux:description>{{ __('Gebruik een positief getal om punten toe te voegen, negatief om te verwijderen. Bijv. 10 of -5.') }}</flux:description>
                        <flux:input
                            wire:model="amount"
                            type="number"
                            placeholder="{{ __('Bijv. 10 of -5') }}"
                        />
                        <flux:error name="amount" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Reden') }}</flux:label>
                        <flux:description>{{ __('Omschrijf waarom deze correctie wordt gemaakt.') }}</flux:description>
                        <flux:textarea
                            wire:model="reason"
                            placeholder="{{ __('Bijv. foutieve toekenning gecorrigeerd...') }}"
                            rows="3"
                        />
                        <flux:error name="reason" />
                    </flux:field>

                    <div class="flex items-center gap-3">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="addCorrection">
                            {{ __('Correctie opslaan') }}
                        </flux:button>
                        <flux:text class="text-xs text-zinc-500" wire:loading wire:target="addCorrection">
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
                                            <flux:badge color="amber" size="sm">{{ __('Admin correctie') }}</flux:badge>
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
                <a href="{{ route('admin.participants.index') }}" class="btn-secondary" wire:navigate>
                    {{ __('Terug naar deelnemers') }}
                </a>
            </div>

        </div>
    </x-pages::admin.layout>
</section>
