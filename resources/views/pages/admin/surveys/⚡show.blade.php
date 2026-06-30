<?php

use App\Models\Survey;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Enquete-inzendingen')] class extends Component {
    use WithPagination;

    public Survey $survey;

    public function mount(): void
    {
        $this->authorize('view', $this->survey);
    }

    public function getResponsesProperty()
    {
        return $this->survey->responses()
            ->visibleInResults()
            ->with('contactInformationSubmission')
            ->latest('submitted_at')
            ->paginate(15);
    }
}; ?>

<section class="w-full" aria-labelledby="admin-survey-show-page-title">
    <x-pages::admin.layout
        :heading="$survey->title"
        :subheading="__('Bekijk ingestuurde inzendingen en open contactgegevens wanneer die zijn gedeeld.')"
        heading-id="admin-survey-show-page-title"
    >
        <div class="space-y-6 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <div>
                <flux:button
                    variant="ghost"
                    icon="arrow-left"
                    :href="route('admin.surveys.index')"
                    wire:navigate
                >
                    {{ __('Terug naar enquetes') }}
                </flux:button>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200" role="status" aria-live="polite">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-xl border border-neutral-200 bg-zinc-50 p-4 dark:border-neutral-700 dark:bg-zinc-800/50">
                <flux:text>{{ $survey->description }}</flux:text>
            </div>

            <flux:table :paginate="$this->responses">
                <flux:table.columns>
                    <flux:table.column>{{ __('Inzending') }}</flux:table.column>
                    <flux:table.column>{{ __('Ingestuurd') }}</flux:table.column>
                    <flux:table.column>{{ __('Contactstatus') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Acties') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->responses as $response)
                        <flux:table.row :key="$response->id">
                            <flux:table.cell variant="strong">#{{ $response->id }}</flux:table.cell>
                            <flux:table.cell>{{ $response->submitted_at?->format('d-m-Y H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($response->hasSharedContactDetails() && $response->contactInformationSubmission)
                                    <flux:badge color="emerald" size="sm">{{ __('Gedeeld') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Niet gedeeld') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button
                                    variant="primary"
                                    size="sm"
                                    icon="arrow-top-right-on-square"
                                    :href="route('admin.responses.show', $response)"
                                    wire:navigate
                                    aria-label="{{ __('Open inzending #:id', ['id' => $response->id]) }}"
                                >
                                    {{ __('Open inzending') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-pages::admin.layout>
</section>
