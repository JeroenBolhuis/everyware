<x-layouts::public :title="$enquete->title">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:text>
                <a href="{{ route('enquetes.show', $enquete) }}" class="underline underline-offset-4" wire:navigate>
                    {{ __('← Terug') }}
                </a>
            </flux:text>

            <flux:heading size="xl">{{ $enquete->title }}</flux:heading>
            @if (filled($enquete->description))
                <flux:text class="text-muted">{{ $enquete->description }}</flux:text>
            @endif
        </div>

        @if ($submitted)
            <flux:callout variant="success">
                <flux:heading>{{ __('Bedankt!') }}</flux:heading>
                <flux:text>{{ __('Je enquete is succesvol verzonden.') }}</flux:text>
            </flux:callout>

            <flux:button :href="route('enquetes.index')" wire:navigate>
                {{ __('Terug naar enquetes') }}
            </flux:button>
        @else
            <form wire:submit="submit" class="space-y-6">
                <div class="space-y-5">
                    @foreach ($enquete->questions as $question)
                        <div wire:key="question-{{ $question->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:field>
                                <flux:label>
                                    {{ $question->label }}
                                    @if ($question->is_required)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </flux:label>

                                @if ($question->type === 'textarea')
                                    <flux:textarea wire:model.live="answers.{{ $question->id }}" />
                                @else
                                    <flux:input wire:model.live="answers.{{ $question->id }}" />
                                @endif

                                <flux:error name="answers.{{ $question->id }}" />
                            </flux:field>
                        </div>
                    @endforeach
                </div>

                <flux:button type="submit" variant="primary">
                    {{ __('Verzenden') }}
                </flux:button>
            </form>
        @endif
    </div>
</x-layouts::public>