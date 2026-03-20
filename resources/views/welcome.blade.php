<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Welcome')])
    </head>
    <body class="flex min-h-screen flex-col items-center bg-zinc-50 p-6 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 lg:justify-center lg:p-8">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-block rounded-sm border border-zinc-200 px-5 py-1.5 leading-normal hover:border-zinc-300 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-zinc-600"
                            wire:navigate
                        >
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block rounded-sm border border-transparent px-5 py-1.5 leading-normal hover:border-zinc-200 dark:hover:border-zinc-700"
                            wire:navigate
                        >
                            {{ __('Log in') }}
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block rounded-sm border border-zinc-200 px-5 py-1.5 leading-normal hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600"
                                wire:navigate
                            >
                                {{ __('Register') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex w-full flex-1 items-center justify-center opacity-100 duration-750 starting:opacity-0 lg:grow">
            <main class="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="flex-1 rounded-es-lg rounded-ee-lg bg-white p-6 pb-12 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10 lg:-ms-px lg:mb-0 lg:rounded-ee-none lg:rounded-ss-lg lg:pb-12 lg:p-20">
                    <p class="text-caption mb-2">Leer- en Innovatiecentrum · Avans Hogeschool</p>
                    <h1 class="mb-2">Everyware</h1>
                    <p class="mb-4 text-muted">Enquetes voor iedereen. Met Everyware kan het LIC enquetes aanmaken en uitzetten. Studenten kunnen eenvoudig meedoen en feedback geven voor onderwijs en beleid.</p>
                    <p class="text-muted mb-6">De applicatie is nog in ontwikkeling.</p>
                    <ul class="flex gap-3 leading-normal">
                        <li>
                            <a href="{{ route('login') }}" class="inline-block rounded-sm border border-zinc-900 bg-zinc-900 px-5 py-1.5 text-white hover:border-black hover:bg-black dark:border-zinc-200 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:border-white dark:hover:bg-white" wire:navigate>
                                {{ __('Log in') }}
                            </a>
                        </li>
                        @if (Route::has('register'))
                            <li>
                                <a href="{{ route('register') }}" class="inline-block rounded-sm border border-zinc-200 px-5 py-1.5 leading-normal hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600" wire:navigate>
                                    {{ __('Register') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="relative -mb-px flex w-full shrink-0 items-center justify-center overflow-hidden rounded-t-lg bg-red-50 aspect-[335/376] lg:mb-0 lg:aspect-auto lg:h-auto lg:w-[438px] lg:rounded-t-none lg:rounded-e-lg dark:bg-red-950/30">
                    <div class="p-8 text-center">
                        <h2 class="text-red-600 dark:text-red-500">Everyware</h2>
                        <p class="text-muted mt-2">Enquetes voor het LIC</p>
                    </div>
                </div>
            </main>
        </div>

        @if (Route::has('login'))
            <div class="hidden h-14.5 lg:block"></div>
        @endif
    </body>
</html>
