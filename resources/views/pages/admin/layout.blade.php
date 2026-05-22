@props([
    'heading' => '',
    'subheading' => '',
])

<div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-10">
    <div class="w-full md:w-[220px] md:flex-shrink-0">
        <flux:navlist aria-label="{{ __('Beheer') }}">
            @if (auth()->user()->canReviewSurveyResponses())
                <flux:navlist.item :href="route('admin.surveys.index')" :current="request()->routeIs('admin.surveys.*') || request()->routeIs('admin.responses.*')" wire:navigate>
                    {{ __('Enquete-inzendingen') }}
                </flux:navlist.item>
            @endif

            @if (auth()->user()->canManageUsers())
                <flux:navlist.item :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                    {{ __('Gebruikers') }}
                </flux:navlist.item>
            @endif

            @if (auth()->user()->canReviewSurveyResponses())
                <flux:navlist.item :href="route('admin.participants.index')" :current="request()->routeIs('admin.participants.*')" wire:navigate>
                    {{ __('Deelnemers') }}
                </flux:navlist.item>
            @endif
        </flux:navlist>
    </div>

    <div class="flex-1 w-full">
        <flux:heading>{{ $heading }}</flux:heading>
        <flux:subheading>{{ $subheading }}</flux:subheading>

        <div class="mt-5 w-full max-w-6xl">
            {{ $slot }}
        </div>
    </div>
</div>
