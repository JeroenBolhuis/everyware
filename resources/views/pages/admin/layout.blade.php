@props([
    'heading' => '',
    'subheading' => '',
])

<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
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

                <flux:navlist.item :href="route('admin.participants.index')" :current="request()->routeIs('admin.participants.*')" wire:navigate>
                    {{ __('Deelnemers') }}
                </flux:navlist.item>
            @endif
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading }}</flux:heading>
        <flux:subheading>{{ $subheading }}</flux:subheading>

        <div class="mt-5 w-full max-w-6xl">
            {{ $slot }}
        </div>
    </div>
</div>
