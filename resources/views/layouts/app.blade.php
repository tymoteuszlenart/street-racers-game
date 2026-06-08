<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Street Racers') }}</title>

        <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class([
        'font-sans antialiased bg-racing-900 text-gray-200',
        'pt-6' => $playerHud !== null,
    ])>
        @if ($playerHud !== null)
            <x-player-hud
                :nickname="$playerHud['nickname']"
                :cash="$playerHud['cash']"
                :cups="$playerHud['cups']"
                :fuel-current="$playerHud['fuelCurrent']"
                :fuel-max="$playerHud['fuelMax']"
                :premium-fuel-current="$playerHud['premiumFuelCurrent']"
                :premium-fuel-max="$playerHud['premiumFuelMax']"
                :level="$playerHud['level']"
                :progress="$playerHud['progress']"
                :percent="$playerHud['percent']"
                :max-level="$playerHud['maxLevel']"
            />
        @endif

        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-racing-800 shadow border-b border-racing-600">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
