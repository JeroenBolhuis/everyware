<?php

use App\Actions\Surveys\DeleteSurveySubmission;
use App\Models\Participant;
use App\Models\SurveyResponse;
use App\Services\ParticipantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Enquete-inzending')] class extends Component {
    public SurveyResponse $response;
    public ?string $respondentEmail = null;
    public bool $respondentIsBlocked = false;
    public bool $canViewPersonalData = false;

    public function mount(): void
    {
        $this->authorize('view', $this->response);
        $this->canViewPersonalData = auth()->user()?->isAdmin() === true;
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

        abort_unless($this->canViewPersonalData, 403);

        if ($this->respondentEmail === null) {
            return;
        }

        $survey = $this->response->survey;
        $deleteSurveySubmission = app(DeleteSurveySubmission::class);

        DB::transaction(function () use ($deleteSurveySubmission): void {
            /** @var ParticipantService $service */
            $service = app(ParticipantService::class);
            $service->blockByEmail($this->respondentEmail);
            $deleteSurveySubmission->handle($this->response);
        });

        Session::flash('status', __('De inzending is verwijderd en het e-mailadres is geblokkeerd.'));

        $this->redirect(route('admin.surveys.show', $survey));
    }

    protected function refreshResponse(): void
    {
        $this->response->refresh();
        $this->response->load('survey', 'answers.question', 'participant');

        if (! $this->canViewPersonalData || $this->response->is_anonymous) {
            $this->respondentEmail = null;
            $this->respondentIsBlocked = false;

            return;
        }

        // Email is retrieved from the personal DB via the service — never from the feedback DB.
        /** @var ParticipantService $service */
        $service = app(ParticipantService::class);
        $this->respondentEmail = $this->response->participant_id !== null
            ? $service->emailForParticipant($this->response->participant_id)
            : null;

        $this->respondentIsBlocked = $this->respondentEmail !== null
            && $service->isEmailBlocked($this->respondentEmail);
    }
}; ?>

<section class="w-full" aria-labelledby="admin-response-show-page-title">
    @include('partials.admin-heading')

    <flux:heading class="sr-only" id="admin-response-show-page-title">{{ __('Enquete-inzending') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Inzending #:id', ['id' => $response->id])"
        :subheading="$response->survey->title"
    >
        <div class="my-6 space-y-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <a href="{{ route('admin.surveys.show', $response->survey) }}" class="btn-secondary" wire:navigate>{{ __('Terug naar enquete-inzendingen') }}</a>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    @if ($canViewPersonalData && $respondentEmail !== null && ! $respondentIsBlocked)
                        <flux:modal.trigger name="confirm-respondent-blocking">
                            <flux:button
                                variant="danger"
                                type="button"
                                icon="no-symbol"
                            >
                                {{ __('E-mailadres blokkeren') }}
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

            @if ($respondentIsBlocked)
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950 dark:border-rose-800/70 dark:bg-rose-950/30 dark:text-rose-100">
                    {{ __('Dit e-mailadres is geblokkeerd. Nieuwe inzendingen met dit e-mailadres worden automatisch verwijderd.') }}
                </div>
            @endif

            @if ($canViewPersonalData && $respondentEmail !== null && ! $respondentIsBlocked)
                <flux:modal name="confirm-respondent-blocking" class="max-w-lg">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('E-mailadres blokkeren?') }}</flux:heading>

                            <flux:subheading class="mt-2">
                                {{ __('Dit blokkeert :email voor toekomstige enquête-inzendingen en verwijdert deze huidige inzending direct.', ['email' => $respondentEmail]) }}
                            </flux:subheading>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800/70 dark:bg-amber-950/30 dark:text-amber-100">
                            {{ __('Gebruik dit alleen wanneer je wilt voorkomen dat deze persoon opnieuw enquêtes kan insturen met hetzelfde e-mailadres.') }}
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
                                {{ __('Blokkeren en verwijderen') }}
                            </flux:button>
                        </div>
                    </div>
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

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Inzendingsdetails') }}</flux:heading>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-zinc-500">{{ __('Ingestuurd') }}</dt>
                            <dd>{{ $response->submitted_at?->format('d-m-Y H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500">{{ __('Intrekkingsstatus') }}</dt>
                            <dd>{{ $response->withdrawn_at ? __('Ingetrokken') : __('Actief') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Deelnemer') }}</flux:heading>

                @if (! $canViewPersonalData)
                    <flux:text class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('Je hebt geen rechten om deze persoonsgegevens te bekijken.') }}
                    </flux:text>
                @elseif (! $response->is_anonymous)
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-zinc-500">{{ __('E-mail') }}</dt>
                            <dd>{{ $response->participant?->email ?: '—' }}</dd>
                        </div>
                    </dl>
                @else
                    <flux:text class="mt-4">{{ __('Deze inzending is anoniem. De deelnemer en het e-mailadres worden niet getoond.') }}</flux:text>
                @endif
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Antwoorden') }}</flux:heading>

                <div class="mt-4 space-y-4">
                    @forelse ($response->answers as $answer)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="answer-{{ $answer->id }}">
                            <div class="min-w-0">
                                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $answer->question?->question ?? __('Vraag verwijderd') }}
                                </flux:text>
                                <flux:text class="mt-2 whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $answer->answer }}
                                </flux:text>
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Er zijn geen antwoorden meer zichtbaar voor deze inzending.') }}
                        </flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
