<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} - {{ config('app.name', 'Everyware') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="stylesheet" href="{{ asset('error.css') }}">
    </head>
    <body>
        <main class="error-page">
            <section class="error-panel" aria-labelledby="error-title">
                <p class="error-code">
                    Fout {{ $status }}
                </p>

                <h1 id="error-title" class="error-title">
                    {{ $title }}
                </h1>

                <p class="error-message">
                    {{ $message }}
                </p>

                <div class="error-actions">
                    <a href="{{ url('/') }}" class="error-button error-button-primary">
                        Naar de startpagina
                    </a>

                    <a href="{{ url('/surveys') }}" class="error-button">
                        Naar enquetes
                    </a>
                </div>
            </section>
        </main>
    </body>
</html>
