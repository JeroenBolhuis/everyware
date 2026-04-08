<?php

use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Enquete-inzending')] class extends Component {
    public SurveyResponse $response;

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->authorize('view', $this->response);
        $this->refreshResponse();
    }

    public function deleteAnswer(int $answerId): void
    {
        $this->authorize('deleteAnswer', $this->response);

        $answer = SurveyAnswer::query()
            ->whereBelongsTo($this->response, 'response')
            ->whereKey($answerId)
            ->firstOrFail();

        $answer->delete();

        $this->statusMessage = __('Het antwoord is succesvol verwijderd.');

        $this->refreshResponse();
    }

    protected function refreshResponse(): void
    {
        $this->response->refresh();
        $this->response->load('survey', 'answers.question', 'contactInformationSubmission');
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Enquete-inzending') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Inzending #:id', ['id' => $response->id])"
        :subheading="$response->survey->title"
    >
        <div class="my-6 space-y-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div>
                <a href="{{ route('admin.surveys.show', $response->survey) }}" class="btn-secondary" wire:navigate>{{ __('Terug naar enquete-inzendingen') }}</a>
            </div>

            @if ($statusMessage)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ $statusMessage }}
                </div>
            @endif

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
                    <flux:heading size="lg">{{ __('Contactgegevens') }}</flux:heading>

                    @if ($response->hasSharedContactDetails())
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-zinc-500">{{ __('Naam') }}</dt>
                                <dd>{{ $response->contactInformationSubmission?->name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500">{{ __('E-mail') }}</dt>
                                <dd>{{ $response->contactInformationSubmission?->email ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500">{{ __('Telefoon') }}</dt>
                                <dd>{{ $response->contactInformationSubmission?->phone ?: '—' }}</dd>
                            </div>
                        </dl>
                    @else
                        <flux:text class="mt-4">{{ __('Er zijn geen contactgegevens gedeeld voor deze inzending.') }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Antwoorden') }}</flux:heading>

                <div class="mt-4 space-y-4">
                    @forelse ($response->answers as $answer)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="answer-{{ $answer->id }}">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $answer->question?->question ?? __('Vraag verwijderd') }}
                                    </flux:text>
                                    <flux:text class="mt-2 whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $answer->answer }}
                                    </flux:text>
                                </div>

                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    type="button"
                                    icon="trash"
                                    wire:click="deleteAnswer({{ $answer->id }})"
                                    wire:confirm="{{ __('Weet je zeker dat je dit antwoord wilt verwijderen?') }}"
                                >
                                    {{ __('Antwoord verwijderen') }}
                                </flux:button>
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
