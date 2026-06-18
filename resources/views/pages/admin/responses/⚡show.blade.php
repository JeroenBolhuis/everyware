<?php

use App\Actions\Surveys\DeleteSurveySubmission;
use App\Mail\AdminParticipantMessageMail;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\ParticipantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Enquete-inzending')] class extends Component {
    public SurveyResponse $response;
    public bool $respondentIsBlocked = false;
    public string $mailSubject = '';
    public string $mailMessage = '';

    public function mount(): void
    {
        $this->authorize('view', $this->response);
        $this->refreshResponse();
    }

    public function deleteSubmission(): void
    {
        $this->authorize('delete', $this->response);

        $survey = $this->response->survey;
        $deleteSurveySubmission = app(DeleteSurveySubmission::class);

        DB::transaction(function () use ($deleteSurveySubmission): void {
            $deleteSurveySubmission->handle($this->response);
        });

        Session::flash('status', __('De inzending is succesvol verwijderd.'));

        $this->redirect(route('admin.surveys.show', $survey));
    }

    public function blockRespondent(): void
    {
        $this->authorize('delete', $this->response);

        if ($this->response->participant === null) {
            return;
        }

        $this->response->participant->block();

        Session::flash('status', __('De deelnemer is geblokkeerd.'));

        $this->refreshResponse();
    }

    public function sendRespondentMessage(ParticipantService $participantService): void
    {
        $this->authorize('view', $this->response);

        if ($this->response->participant_id === null || $this->response->is_anonymous) {
            abort(404);
        }

        $validated = $this->validate([
            'mailSubject' => ['required', 'string', 'max:255'],
            'mailMessage' => ['required', 'string', 'max:5000'],
        ], [
            'mailSubject.required' => 'Vul een onderwerp in.',
            'mailMessage.required' => 'Vul een bericht in.',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $email = $participantService->emailForParticipantMessage($user, $this->response->participant_id);

        if ($email === null) {
            $this->addError('mailSubject', __('Er is geen e-mailadres beschikbaar voor deze deelnemer.'));

            return;
        }

        Mail::to($email)->send(new AdminParticipantMessageMail(
            $validated['mailSubject'],
            $validated['mailMessage'],
            null,
            $this->response->survey?->title,
        ));

        $this->reset('mailSubject', 'mailMessage');

        Session::flash('status', __('Bericht succesvol verstuurd.'));
    }

    protected function refreshResponse(): void
    {
        $this->response->refresh();
        $this->response->load('survey', 'answers.question', 'participant');

        $this->respondentIsBlocked = $this->response->participant?->isBlocked() ?? false;
    }
}; ?>

<section class="w-full" aria-labelledby="admin-response-show-page-title">
    <x-pages::admin.layout
        :heading="__('Inzending #:id', ['id' => $response->id])"
        :subheading="$response->survey->title"
        heading-id="admin-response-show-page-title"
    >
        <div class="space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200" role="status" aria-live="polite">
                    {{ session('status') }}
                </div>
            @endif

            @if ($respondentIsBlocked)
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950 dark:border-rose-800/70 dark:bg-rose-950/30 dark:text-rose-100">
                    {{ __('Deze deelnemer is geblokkeerd. Nieuwe inzendingen van dit volgnummer worden niet meegenomen in resultaten.') }}
                </div>
            @endif

            @if ($response->participant !== null && ! $respondentIsBlocked)
                <flux:modal name="confirm-respondent-blocking" class="max-w-lg">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Deelnemer blokkeren?') }}</flux:heading>

                            <flux:subheading class="mt-2">
                                {{ __('Dit blokkeert volgnummer :code voor toekomstige enquête-inzendingen.', ['code' => $response->participant->displayNameFor(auth()->user())]) }}
                            </flux:subheading>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800/70 dark:bg-amber-950/30 dark:text-amber-100">
                            {{ __('Gebruik dit alleen wanneer je wilt voorkomen dat deze deelnemer opnieuw enquêtes kan insturen.') }}
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
                                wire:click="blockRespondent"
                                wire:loading.attr="disabled"
                                wire:target="blockRespondent"
                            >
                                {{ __('Blokkeren') }}
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>
            @endif

            @if ($response->participant !== null && ! $response->is_anonymous)
                <flux:modal name="mail-response-participant" class="max-w-xl">
                    <form wire:submit="sendRespondentMessage" class="space-y-6">
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

                            <flux:button type="submit" variant="primary" icon="envelope" wire:loading.attr="disabled" wire:target="sendRespondentMessage">
                                {{ __('Versturen') }}
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endif

            <flux:modal name="confirm-submission-deletion" class="max-w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Volledige inzending verwijderen?') }}</flux:heading>

                        <flux:subheading class="mt-2">
                            {{ __('Je verwijdert hiermee alle antwoorden, gedeelde contactgegevens en gekoppelde puntenhistorie van deze inzending. Deze actie kan niet ongedaan worden gemaakt.') }}
                        </flux:subheading>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800/70 dark:bg-amber-950/30 dark:text-amber-100">
                        {{ __('Controleer goed of je de volledige inzending van deze gebruiker wilt verwijderen voordat je doorgaat.') }}
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
                            icon="trash"
                            wire:click="deleteSubmission"
                            wire:loading.attr="disabled"
                            wire:target="deleteSubmission"
                        >
                            {{ __('Definitief verwijderen') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>

            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <flux:button
                        variant="ghost"
                        icon="arrow-left"
                        :href="route('admin.surveys.show', $response->survey)"
                        wire:navigate
                    >
                        {{ __('Terug naar enquete-inzendingen') }}
                    </flux:button>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        @if ($response->participant !== null && ! $response->is_anonymous)
                            <flux:modal.trigger name="mail-response-participant">
                                <flux:button
                                    variant="ghost"
                                    type="button"
                                    icon="envelope"
                                >
                                    {{ __('Student mailen') }}
                                </flux:button>
                            </flux:modal.trigger>
                        @endif

                        @if ($response->participant !== null && ! $respondentIsBlocked)
                            <flux:modal.trigger name="confirm-respondent-blocking">
                                <flux:button
                                    variant="danger"
                                    type="button"
                                    icon="no-symbol"
                                >
                                    {{ __('Deelnemer blokkeren') }}
                                </flux:button>
                            </flux:modal.trigger>
                        @endif

                        <flux:modal.trigger name="confirm-submission-deletion">
                            <flux:button
                                variant="danger"
                                type="button"
                                icon="trash"
                            >
                                {{ __('Inzending verwijderen') }}
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>

                <dl class="mt-6 grid gap-x-8 gap-y-5 border-y border-neutral-200 py-5 text-sm sm:grid-cols-2 lg:grid-cols-4 dark:border-neutral-700">
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Ingestuurd') }}</dt>
                        <dd class="mt-1 text-zinc-950 dark:text-zinc-100">{{ $response->submitted_at?->format('d-m-Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-zinc-950 dark:text-zinc-100">{{ $response->withdrawn_at ? __('Ingetrokken') : __('Actief') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-zinc-950 dark:text-zinc-100">{{ $response->is_anonymous ? __('Anoniem') : __('Niet anoniem') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Volgnummer') }}</dt>
                        <dd class="mt-1 text-zinc-950 dark:text-zinc-100">
                            {{ $response->participant?->displayNameFor(auth()->user()) ?? __('Geen deelnemer') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <flux:heading size="lg">{{ __('Antwoorden') }}</flux:heading>

                    <div class="mt-4 divide-y divide-neutral-200 border-y border-neutral-200 dark:divide-neutral-700 dark:border-neutral-700">
                        @forelse ($response->answers as $answer)
                            <div class="grid gap-3 py-5 lg:grid-cols-[minmax(16rem,24rem)_minmax(0,1fr)] lg:gap-8" wire:key="answer-{{ $answer->id }}">
                                <div class="min-w-0">
                                    <flux:text class="font-medium leading-6 text-zinc-950 dark:text-zinc-100">
                                        {{ $answer->question?->question ?? __('Vraag verwijderd') }}
                                    </flux:text>
                                </div>

                                <flux:text class="whitespace-pre-wrap leading-6 text-zinc-700 dark:text-zinc-300">
                                    {{ filled($answer->answer) ? $answer->answer : '—' }}
                                </flux:text>
                            </div>
                        @empty
                            <flux:text class="py-5 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Er zijn geen antwoorden meer zichtbaar voor deze inzending.') }}
                            </flux:text>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
