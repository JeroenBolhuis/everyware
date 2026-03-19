<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <x-app-logo href="{{ route('home') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                    {{ __('Home') }}
                </flux:navbar.item>
                <flux:navbar.item icon="clipboard-document-list" :href="route('enquetes.index')" :current="request()->routeIs('enquetes.*')" wire:navigate>
                    {{ __('Enquetes') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            @if (Route::has('login'))
                @auth
                    <flux:button :href="route('dashboard')" variant="primary" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:button>
                @else
                    <flux:button :href="route('login')" variant="primary" wire:navigate>
                        {{ __('Log in') }}
                    </flux:button>
                @endauth
            @endif
        </flux:header>

        <main class="mx-auto w-full max-w-4xl px-6 py-10">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>

