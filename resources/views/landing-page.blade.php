<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    @laravelPWA

    <style>

        :root {
            --custom-1: #bf0413;
            --custom-2: #4c6173;
            --custom-3: #95acbf;
            --custom-4: #f28705;
            --custom-5: #f20505;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #222;
            background: url("images/landing-page.jpg");
            background-position: center;
            background-size: cover;
            color: #fff;
            font-size: 100%;
            min-height: 100vh;
            width: 100vw;
        }

        main {
            display: inline-grid;
            grid-gap: calc(0.25em + 4vh);
            grid-template-columns: 1fr;
        }

        h1 {
            font-size: calc(2em + 2vw);
            display: inline-block;
            padding: 0.25em;
            margin-top: calc(1em + 5vh);
        }

        p {
            line-height: 1.6;
            padding: 1em;
            width: calc(30ch + 5vw);
        }

        h1, p {
            background: rgba(15,15,15,0.85);
            border-bottom-right-radius: 4px;
            border-top-right-radius: 4px;
            box-shadow: 0 0 5px 3px #000;
            padding-left: 6vw; /* sync with a:last-child */
        }

        a:last-child {
            background: #fff;
            border: 2px solid var(--custom-4);
            border-radius: 4px;
            box-shadow: 0 0 2px 2px #fff;
            color: #333;
            letter-spacing: 1px;
            line-height: 1.6em;
            margin-left: 6vw; /* sync with h1,p */
            margin-bottom: 2em;
            max-width: calc(88vw); /* 100wv - 2*6vw */
            padding: 1em 2.5em;
            position: relative;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            width: calc(100% - 6vw);
        }

        a:last-child::before {
            border: 2px solid var(--custom-1);
            border-radius: 6px;
            bottom: -6px;
            box-shadow: 0 0 4px 2px #fff;
            content: '';
            left: -6px;
            position: absolute;
            right: -6px;
            top: -6px;
        }

    </style>
</head>
<body>

<main>
    <header>
        <h1>@lang('landing-page.title')</h1>
    </header>

    <p>
        @lang('landing-page.p1')
    </p>
    <p>
        @lang('landing-page.p2')
    </p>

    <a href="{{ route('login') }}">@lang('landing-page.link')</a>

</main>
</body>
</html>
