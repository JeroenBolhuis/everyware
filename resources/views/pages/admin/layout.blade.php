@props([
    'heading' => '',
    'subheading' => '',
    'headingId' => 'admin-page-heading',
])

<div class="flex w-full flex-col">
    <header class="mb-6">
        <flux:heading size="xl" level="1" :id="$headingId">{{ $heading }}</flux:heading>

        @if (filled($subheading))
            <flux:subheading class="mt-1">{{ $subheading }}</flux:subheading>
        @endif

        <flux:separator variant="subtle" class="mt-6" />
    </header>

    <div class="w-full max-w-6xl space-y-6">
        {{ $slot }}
    </div>
</div>
