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
            ->with('contactInformationSubmission')
            ->latest('submitted_at')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Enquete-inzendingen') }}</flux:heading>

    <x-pages::admin.layout
        :heading="$survey->title"
        :subheading="__('Bekijk ingestuurde inzendingen en open contactgegevens wanneer die zijn gedeeld.')"
    >
        <div class="my-6 space-y-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div>
                <a href="{{ route('admin.surveys.index') }}" class="btn-secondary" wire:navigate>{{ __('Terug naar enquetes') }}</a>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text>{{ $survey->description }}</flux:text>
            </div>

            <flux:table :paginate="$this->responses">
                <flux:table.columns>
                    <flux:table.column>{{ __('Inzending') }}</flux:table.column>
                    <flux:table.column>{{ __('Ingestuurd') }}</flux:table.column>
                    <flux:table.column>{{ __('Contactgegevens') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Acties') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->responses as $response)
                        <flux:table.row :key="$response->id">
                            <flux:table.cell variant="strong">#{{ $response->id }}</flux:table.cell>
                            <flux:table.cell>{{ $response->submitted_at?->format('d-m-Y H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$response->hasSharedContactDetails() ? 'emerald' : 'zinc'" size="sm">
                                    {{ $response->hasSharedContactDetails() ? __('Gedeeld') : __('Niet gedeeld') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <a href="{{ route('admin.responses.show', $response) }}" class="btn-primary" wire:navigate>{{ __('Open inzending') }}</a>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-pages::admin.layout>
</section>
