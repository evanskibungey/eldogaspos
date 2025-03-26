<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('settings.company_name', 'EldoGas') }} POS</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Fallback for production if the above fails -->
        @production
            <script>
                // Check if the Vite script failed to load
                if (!window.vite_loaded) {
                    // Fallback to direct asset links
                    const baseUrl = '{{ url('/') }}';
                    document.write(`<link rel="stylesheet" href="${baseUrl}/build/assets/app-6504ce5c.css">`);
                    document.write(`<script type="module" src="${baseUrl}/build/assets/app-69a2ab31.js"><\/script>`);
                }
            </script>
        @endproduction
    </head>
    <body class="font-sans antialiased relative">
        <!-- Top Orange Border -->
        <div class="h-1 bg-orange-500 w-full absolute top-0 left-0 z-50"></div>
        
        <x-sidebar-layout>
            {{ $slot }}
        </x-sidebar-layout>
        
        <!-- Mark that Vite has loaded -->
        <script>window.vite_loaded = true;</script>
    </body>
</html>