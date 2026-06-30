<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.vercel-analytics')
</head>
<body class="bg-zinc-50 text-zinc-900">
    <div class="min-h-screen">
        <x-surveys.page-header/>

        <main>
            {{ $slot }}
        </main>
    </div>

    @auth('participant')
        @if (auth('participant')->user()->onboarded_at === null)
            <x-participant-onboarding-modal/>
        @endif
    @endauth

</body>
</html>
