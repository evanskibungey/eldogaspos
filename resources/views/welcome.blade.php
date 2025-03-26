<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('settings.company_name', 'EldoGas') }} POS</title>
        
        <!-- Include Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            orange: {
                                50: '#fff7ed',
                                100: '#ffedd5',
                                200: '#fed7aa',
                                300: '#fdba74',
                                400: '#fb923c',
                                500: '#f97316',
                                600: '#ea580c',
                                700: '#c2410c',
                                800: '#9a3412',
                                900: '#7c2d12',
                            }
                        }
                    }
                }
            }
        </script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .animate-float {
                animation: float 3s ease-in-out infinite;
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
                100% { transform: translateY(0px); }
            }
        </style>
    </head>
    <body class="bg-gray-50">
        <!-- Orange Top Border -->
        <div class="h-1 bg-orange-500 w-full"></div>
        
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-white rounded-full h-10 w-10 flex items-center justify-center shadow-sm">
                            @if(config('settings.company_logo'))
                                <img src="{{ Storage::url(config('settings.company_logo')) }}" 
                                     alt="{{ config('settings.company_name', 'EldoGas') }}" 
                                     class="h-8 w-8 object-contain" />
                            @else
                                <svg class="h-8 w-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v16h10V4H7zm2 3h6v2H9V7zm0 4h6v2H9v-2zm0 4h6v2H9v-2z"/>
                                </svg>
                            @endif
                        </div>
                        <span class="ml-3 text-xl font-bold text-gray-900">
                            <span>{{ config('settings.company_name', 'Eldo') }}</span><span class="text-orange-500">_POS</span>
                        </span>
                    </div>
                    
                    <div class="flex items-center">
                        @auth
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('pos.dashboard') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                                    Dashboard
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" 
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out mr-3">
                                Log in
                            </a>
                            <a href="{{ route('dashboard') }}" 
                               class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                                Dashboard
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="mt-8 sm:mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                    <!-- Left Content -->
                    <div class="sm:text-center md:max-w-2xl md:mx-auto lg:col-span-6 lg:text-left">
                        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block">Streamline Your</span>
                            <span class="block text-orange-600">LPG Business</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            A modern point of sale system designed specifically for LPG retailers
                        </p>
                        <div class="mt-8 sm:max-w-lg sm:mx-auto sm:text-center lg:text-left lg:mx-0">
                            @auth
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" 
                                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 transition duration-300 ease-in-out">
                                        Go to Dashboard
                                        <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('pos.dashboard') }}" 
                                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 transition duration-300 ease-in-out">
                                        Go to POS
                                        <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('login') }}" 
                                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 transition duration-300 ease-in-out">
                                    Get Started
                                    <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endauth
                        </div>
                    </div>
                    
                    <!-- Right Content -->
                    <div class="mt-12 relative sm:max-w-lg sm:mx-auto lg:mt-0 lg:max-w-none lg:mx-0 lg:col-span-6 lg:flex lg:items-center">
                        <div class="relative mx-auto w-full rounded-lg shadow-lg lg:max-w-md">
                            <div class="relative block w-full bg-white rounded-lg overflow-hidden">
                                <div class="w-full">
                                    <div class="relative">
                                        <!-- Main Illustration -->
                                        <div class="p-8 flex flex-col items-center">
                                            <div class="w-40 h-40 bg-orange-100 rounded-full flex items-center justify-center animate-float mb-6">
                                                <svg class="h-24 w-24 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M17 7h1v1h-1V7zm-2-2h1v1h-1V5zm-8 3h10V5a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3v3zm9-2h1a1 1 0 0 1 1 1v1h-2V6zM6 6h2v1H6V6zm2-1a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1H8V5zm6 1h2v1h-2V6zm4 4H6v11a3 3 0 0 0 3 3h6a3 3 0 0 0 3-3V10zm-8 9h8v1H8v-1zm0-2h8v1H8v-1zm0-2h4v1H8v-1z"/>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900">Simple, Fast & Reliable</h3>
                                            <p class="mt-2 text-sm text-gray-500 text-center">
                                                Our POS system is designed to make your LPG business management effortless
                                            </p>
                                        </div>
                                        <!-- Features Pill Cards -->
                                        <div class="absolute -top-4 -right-4">
                                            <div class="bg-orange-500 rounded-full px-3 py-1 text-xs font-medium text-white shadow-md">
                                                Inventory Tracking
                                            </div>
                                        </div>
                                        <div class="absolute top-1/4 -left-4">
                                            <div class="bg-orange-500 rounded-full px-3 py-1 text-xs font-medium text-white shadow-md">
                                                Sales Management
                                            </div>
                                        </div>
                                        <div class="absolute bottom-1/4 -right-4">
                                            <div class="bg-orange-500 rounded-full px-3 py-1 text-xs font-medium text-white shadow-md">
                                                Customer Database
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-16">
            <div class="max-w-7xl mx-auto py-6 px-4 overflow-hidden sm:px-6 lg:px-8">
                <div class="mt-4 flex justify-center space-x-6">
                    <p class="text-center text-sm text-gray-500">
                        &copy; {{ date('Y') }} {{ config('settings.company_name', 'EldoGas') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>