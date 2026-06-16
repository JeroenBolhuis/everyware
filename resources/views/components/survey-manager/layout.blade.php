@props([
    'heading' => '',
    'subheading' => '',
    'headingId' => 'survey-manager-page-title',
])

<div class="flex w-full flex-col">
    <header class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl" level="1" :id="$headingId">{{ $heading }}</flux:heading>

                @if (filled($subheading))
                    <flux:subheading class="mt-1">{{ $subheading }}</flux:subheading>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </header>

    <div class="w-full max-w-6xl space-y-6">
        {{ $slot }}
    </div>
</div>
