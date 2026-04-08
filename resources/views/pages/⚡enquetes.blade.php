<?php

use App\Models\Survey;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Enquetes')] class extends Component {
    public function render(): \Illuminate\View\View
    {
        return view('pages.enquetes', [
            'surveys' => Survey::query()->latest()->get(),
        ]);
    }
}; ?>

<x-layouts::app :title="__('Enquetes')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <p class="text-muted">{{ __('Hieronder staan alle enquêtes. Kopieer de link om te delen met studenten.') }}</p>

        @if ($surveys->isEmpty())
            <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 min-h-[12rem] h-full">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                <div class="absolute inset-0 flex items-center justify-center p-6">
                    <span class="text-muted">{{ __('Nog geen enquêtes aangemaakt.') }}</span>
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-800 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-300">{{ __('Titel') }}</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-300">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-300">{{ __('Deelbare link') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($surveys as $survey)
                            <tr class="bg-white dark:bg-neutral-900">
                                <td class="px-4 py-3 font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $survey->title }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($survey->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ __('Actief') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                            {{ __('Inactief') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($survey->is_active)
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="text"
                                                readonly
                                                value="{{ route('surveys.show', $survey) }}"
                                                class="w-full max-w-md rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                                onclick="this.select()"
                                            >
                                        </div>
                                    @else
                                        <span class="text-neutral-400 text-xs">{{ __('—') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts::app>

