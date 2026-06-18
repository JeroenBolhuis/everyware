<?php

use App\Mail\AdminParticipantMessageMail;
use App\Models\Participant;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\ParticipantService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Deelnemer')] class extends Component {
    public Participant $participant;

    public string $mailSubject = '';

    public string $mailMessage = '';

    public function mount(): void
    {
        $this->authorize('view', $this->participant);

        $this->participant->load([
            'surveyResponses' => fn ($query) => $query
                ->visibleInResults()
                ->with('survey')
                ->latest('submitted_at'),
        ]);
    }

    public function blockParticipant(): void
    {
        $this->authorize('view', $this->participant);

        $this->participant->block();
        $this->participant->refresh();
        $this->participant->load([
            'surveyResponses' => fn ($query) => $query
                ->visibleInResults()
                ->with('survey')
                ->latest('submitted_at'),
        ]);

        Session::flash('status', __('Deelnemer succesvol geblokkeerd.'));
    }

    public function sendResponseMessage(int $responseId, ParticipantService $participantService): void
    {
        $this->authorize('view', $this->participant);

        $validated = $this->validate([
            'mailSubject' => ['required', 'string', 'max:255'],
            'mailMessage' => ['required', 'string', 'max:5000'],
        ], [
            'mailSubject.required' => 'Vul een onderwerp in.',
            'mailMessage.required' => 'Vul een bericht in.',
        ]);

        $response = SurveyResponse::query()
            ->whereKey($responseId)
            ->where('participant_id', $this->participant->id)
            ->where('is_anonymous', false)
            ->first();

        abort_unless($response instanceof SurveyResponse, 404);

        /** @var User $user */
        $user = auth()->user();
        $email = $participantService->emailForParticipantMessage($user, $this->participant->id);

        if ($email === null) {
            $this->addError('mailSubject', __('Er is geen e-mailadres beschikbaar voor deze deelnemer.'));

            return;
        }

        Mail::to($email)->send(new AdminParticipantMessageMail(
            $validated['mailSubject'],
            $validated['mailMessage'],
            null,
            $response->survey?->title,
        ));

        $this->reset('mailSubject', 'mailMessage');

        Session::flash('status', __('Bericht succesvol verstuurd.'));
    }
}; ?>

<section class="w-full" aria-labelledby="admin-participant-show-page-title">
    <x-pages::admin.layout
        :heading="$participant->displayNameFor(auth()->user())"
        :subheading="__('Inzendingen en blokkering voor dit volgnummer.')"
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
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Volgnummer') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->displayNameFor(auth()->user()) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Status') }}</flux:text>
                    <div class="mt-2">
                        @if ($participant->isBlocked())
                            <flux:badge color="red">{{ __('Geblokkeerd') }}</flux:badge>
                        @else
                            <flux:badge color="emerald">{{ __('Actief') }}</flux:badge>
                        @endif
                    </div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Inzendingen') }}</flux:text>
                    <flux:heading class="mt-1">{{ $participant->surveyResponses->count() }}</flux:heading>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="lg">{{ __('Inzendingen') }}</flux:heading>
                        <flux:subheading class="mt-1">{{ __('Alle zichtbare inzendingen van dit volgnummer.') }}</flux:subheading>
                    </div>

                    @if (! $participant->isBlocked())
                        <flux:modal.trigger name="confirm-participant-blocking">
                            <flux:button variant="danger" icon="no-symbol" type="button">
                                {{ __('Blokkeren') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>

                <div class="mt-4">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Enquete') }}</flux:table.column>
                            <flux:table.column>{{ __('Ingestuurd') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                            <flux:table.column>{{ __('Type') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('Acties') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($participant->surveyResponses as $response)
                                <flux:table.row :key="$response->id">
                                    <flux:table.cell variant="strong">{{ $response->survey?->title ?? __('Enquete verwijderd') }}</flux:table.cell>
                                    <flux:table.cell>{{ $response->submitted_at?->format('d-m-Y H:i') ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if ($response->withdrawn_at)
                                            <flux:badge color="amber" size="sm">{{ __('Ingetrokken') }}</flux:badge>
                                        @else
                                            <flux:badge color="emerald" size="sm">{{ __('Actief') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($response->is_anonymous)
                                            <flux:badge color="zinc" size="sm">{{ __('Anoniem') }}</flux:badge>
                                        @else
                                            <flux:badge color="blue" size="sm">{{ __('Niet anoniem') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                            @if (! $response->is_anonymous)
                                                <flux:modal.trigger name="mail-participant-response-{{ $response->id }}">
                                                    <flux:button variant="ghost" size="sm" icon="envelope" type="button">
                                                        {{ __('Mailen') }}
                                                    </flux:button>
                                                </flux:modal.trigger>
                                            @endif

                                            <flux:button
                                                variant="primary"
                                                size="sm"
                                                icon="arrow-top-right-on-square"
                                                :href="route('admin.responses.show', $response)"
                                                wire:navigate
                                            >
                                                {{ __('Open inzending') }}
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5">
                                        <flux:text class="text-center text-zinc-500">
                                            {{ __('Nog geen zichtbare inzendingen voor dit volgnummer.') }}
                                        </flux:text>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            @foreach ($participant->surveyResponses as $response)
                @if (! $response->is_anonymous)
                    <flux:modal name="mail-participant-response-{{ $response->id }}" class="max-w-xl">
                        <form wire:submit="sendResponseMessage({{ $response->id }})" class="space-y-6">
                            <div>
                                <flux:heading size="lg">{{ __('Student mailen') }}</flux:heading>
                                <flux:subheading class="mt-2">
                                    {{ __('Dit bericht wordt via het systeem verstuurd. Het e-mailadres wordt niet getoond.') }}
                                </flux:subheading>
                            </div>

                            <flux:field>
                                <flux:label>{{ __('Onderwerp') }}</flux:label>
                                <flux:input wire:model="mailSubject" />
                                <flux:error name="mailSubject" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Bericht') }}</flux:label>
                                <flux:textarea wire:model="mailMessage" rows="5" />
                                <flux:error name="mailMessage" />
                            </flux:field>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <flux:modal.close>
                                    <flux:button variant="ghost" type="button">
                                        {{ __('Annuleren') }}
                                    </flux:button>
                                </flux:modal.close>

                                <flux:button type="submit" variant="primary" icon="envelope" wire:loading.attr="disabled" wire:target="sendResponseMessage">
                                    {{ __('Versturen') }}
                                </flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endif
            @endforeach

            <flux:modal name="confirm-participant-blocking" class="max-w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Deelnemer blokkeren?') }}</flux:heading>
                        <flux:subheading class="mt-2">
                            {{ __('Dit blokkeert dit volgnummer voor toekomstige enquête-inzendingen.') }}
                        </flux:subheading>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost" type="button">
                                {{ __('Annuleren') }}
                            </flux:button>
                        </flux:modal.close>

                        <flux:button
                            variant="danger"
                            type="button"
                            icon="no-symbol"
                            wire:click="blockParticipant"
                            wire:loading.attr="disabled"
                            wire:target="blockParticipant"
                        >
                            {{ __('Blokkeren') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>

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
