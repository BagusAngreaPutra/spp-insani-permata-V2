<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Permata Insani') }}</title>
    @include('layouts.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            color: #101828;
            background: #f7f8fa;
            font-family: "Inter", system-ui, sans-serif;
        }
        .guest-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .guest-panel {
            width: min(100%, 420px);
            padding: 28px;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 10px;
            box-shadow: none;
        }
        .guest-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            color: #101828;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }
        .guest-brand img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <main class="guest-shell">
        <section class="guest-panel">
            <a class="guest-brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="">
                <span>Permata Insani</span>
            </a>
            {{ $slot }}
        </section>
    </main>
</body>
</html>
