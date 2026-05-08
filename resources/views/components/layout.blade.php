<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.vercel-analytics')
</head>
<body class="bg-gray-50">

    <main>
        {{ $slot }}
    </main>

</body>
</html>
