<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('settings.company_name', 'EldoGas') }}</title>

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
            {{--
                Eleven views pass an <x-slot name="header">, and nothing had
                ever rendered it - so their titles vanished and, worse, the
                action buttons inside them did too. "Send bulk SMS", "New
                Category", "New User" and "Add Product" were all unreachable
                except by typing the URL.

                Rendered here rather than in the sidebar's own top bar: that
                bar names the section from the route, this names the page and
                carries its actions.
            --}}
            @isset($header)
                <div class="mb-6">
                    {{ $header }}
                </div>
            @endisset

            {{ $slot }}
        </x-sidebar-layout>

        {{-- Outside the sidebar layout so no ancestor transform or overflow can clip it. --}}
        <x-confirm-dialog />

        {{--
            Nine views @push('scripts') and this stack was never rendered, so
            every one of those blocks was silently discarded - including the six
            that load Chart.js. That is why the dashboard and every report
            showed empty chart canvases.
        --}}
        @stack('scripts')

        <!-- Mark that Vite has loaded -->
        <script>window.vite_loaded = true;</script>
    </body>
</html>