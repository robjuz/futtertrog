<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    @laravelPWA
    <link href="https://fonts.googleapis.com/css?family=Caveat|Livvic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script>
        window.Futtertrog = @json([
            'user' => Auth::user(),
            'csrf' => csrf_token()
        ]);
    </script>
</head>
<body id="offline">
    <main>
        <h1>
            You are currently not connected to any networks.
        </h1>

        @svg('solid/wifi', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
    </main>
</body>
