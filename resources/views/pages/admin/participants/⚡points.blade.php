<?php

use App\Actions\Participants\AdjustParticipantPoints;
use App\Models\Participant;
use App\Services\ParticipantService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Punten aanpassen')] class extends Component {
    #[Validate('required|email:rfc|max:255', message: [
        'required' => 'Vul een e-mailadres in.',
        'email' => 'Vul een geldig e-mailadres in.',
        'max' => 'Het e-mailadres mag maximaal 255 tekens bevatten.',
    ])]
    public string $emailSearch = '';

    public ?int $emailParticipantId = null;

    public bool $emailLookupAttempted = false;

    public string $mutationType = 'add';

    public ?int $pointsAmount = null;

    public string $reason = '';

    public function mount(): void
    {
        $this->authorize('correctPoints', Participant::class);
    }

    public function findParticipantByEmail(ParticipantService $participantService): void
    {
        $this->authorize('correctPoints', Participant::class);
        $this->validateOnly('emailSearch');

        $participant = $participantService->findParticipantByEmail($this->emailSearch);

        $this->emailParticipantId = $participant?->id;
        $this->emailLookupAttempted = true;
        $this->reset('pointsAmount', 'reason');
        $this->resetErrorBag(['pointsAmount', 'reason']);
    }

    public function adjustEmailParticipantPoints(AdjustParticipantPoints $adjustParticipantPoints): void
    {
        $this->authorize('correctPoints', Participant::class);

        $validated = $this->validate([
            'mutationType' => ['required', Rule::in(['add', 'deduct'])],
            'pointsAmount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'pointsAmount.required' => 'Vul het aantal punten in.',
            'pointsAmount.integer' => 'Het aantal punten moet een heel getal zijn.',
            'pointsAmount.min' => 'Het aantal punten moet minimaal 1 zijn.',
            'reason.required' => 'Geef een reden op voor de correctie.',
            'reason.max' => 'De reden mag maximaal 255 tekens bevatten.',
        ]);

        $participant = $this->emailParticipant;

        if (! $participant instanceof Participant) {
            $this->addError('emailSearch', __('Geen deelnemer gevonden voor dit e-mailadres.'));

            return;
        }

        $amount = (int) $validated['pointsAmount'];
        $signedAmount = $validated['mutationType'] === 'deduct' ? -$amount : $amount;

        if ($participant->current_points + $signedAmount < 0) {
            $this->addError('pointsAmount', __('Je kunt niet meer punten afboeken dan de deelnemer heeft.'));

            return;
        }

        $adjustParticipantPoints($participant, $signedAmount, $validated['reason']);

        $this->reset('pointsAmount', 'reason');
        $this->emailParticipantId = $participant->id;

        session()->flash('status', __('Punten succesvol aangepast.'));
    }

    public function getEmailParticipantProperty(): ?Participant
    {
        return $this->emailParticipantId !== null
            ? Participant::query()->find($this->emailParticipantId)
            : null;
    }
}; ?>

<section class="w-full" aria-labelledby="admin-participant-points-page-title">
    <x-pages::admin.layout
        :heading="__('Deelnemers')"
        :subheading="__('Pas punten aan via e-mail zonder het volgnummer te tonen.')"
        heading-id="admin-participant-points-page-title"
    >
        <x-pages::admin.participants.layout>
            <div class="space-y-6">
                @if (session('status'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200" role="status" aria-live="polite">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="space-y-8">
                        <div>
                            <flux:heading size="lg">{{ __('Zoeken op e-mail') }}</flux:heading>
                            <flux:subheading class="mt-1">
                                {{ __('Gebruik deze flow alleen voor puntencorrecties.') }}
                            </flux:subheading>
                        </div>

                        <form wire:submit="findParticipantByEmail" class="space-y-4">
                            <flux:field>
                                <flux:label>{{ __('E-mailadres') }}</flux:label>
                                <flux:input
                                    type="email"
                                    wire:model="emailSearch"
                                    autocomplete="off"
                                    placeholder="student@example.com"
                                />
                                <flux:error name="emailSearch" />
                            </flux:field>

                            <flux:button type="submit" variant="primary" icon="magnifying-glass" class="w-full justify-center" wire:loading.attr="disabled" wire:target="findParticipantByEmail">
                                {{ __('Zoeken') }}
                            </flux:button>
                        </form>

                        @if ($this->emailParticipant || $emailLookupAttempted)
                            <flux:separator variant="subtle" />
                        @endif

                        @if ($this->emailParticipant)
                            <div class="rounded-lg border border-neutral-200 bg-zinc-50 p-5 dark:border-neutral-700 dark:bg-zinc-800/50">
                                <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Huidige punten') }}</flux:text>
                                <flux:heading class="mt-2 text-4xl">{{ $this->emailParticipant->current_points }}</flux:heading>
                            </div>

                            <form wire:submit="adjustEmailParticipantPoints" class="space-y-5">
                                <div class="grid gap-5 md:grid-cols-2">
                                    <flux:field>
                                        <flux:label>{{ __('Mutatie') }}</flux:label>
                                        <flux:select wire:model="mutationType">
                                            <option value="add">{{ __('Punten erbij') }}</option>
                                            <option value="deduct">{{ __('Punten eraf') }}</option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Aantal punten') }}</flux:label>
                                        <flux:input wire:model="pointsAmount" type="number" min="1" />
                                        <flux:error name="pointsAmount" />
                                    </flux:field>
                                </div>

                                <flux:field>
                                    <flux:label>{{ __('Reden') }}</flux:label>
                                    <flux:textarea wire:model="reason" rows="4" />
                                    <flux:error name="reason" />
                                </flux:field>

                                <flux:button type="submit" variant="primary" class="w-full justify-center" wire:loading.attr="disabled" wire:target="adjustEmailParticipantPoints">
                                    {{ __('Punten opslaan') }}
                                </flux:button>
                            </form>
                        @elseif ($emailLookupAttempted)
                            <div class="rounded-lg border border-neutral-200 bg-zinc-50 p-5 dark:border-neutral-700 dark:bg-zinc-800/50">
                                <flux:badge color="amber" size="sm">{{ __('Niet gevonden') }}</flux:badge>
                                <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('Er is geen deelnemer gevonden voor dit e-mailadres.') }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-pages::admin.participants.layout>
    </x-pages::admin.layout>
</section>
