<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SPP Integration') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=poppins:500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="min-h-screen bg-washed-100 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('img/bg.png') }}');">
        <div class="flex min-h-screen flex-col font-sans text-gray-900 antialiased">
            <x-masthead />

            <div class="flex flex-1 flex-col">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>
