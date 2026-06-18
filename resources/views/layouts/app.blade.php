<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', __('messages.transfer_system')) }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo.png?v=2') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-slate-50 min-h-screen" style="font-family: 'Cairo', sans-serif;">
        <div class="min-h-screen flex flex-col md:flex-row">
            
            <!-- We will rely on the page components to render the sidebar, or we can keep navigation here.
                 Since this is a full dashboard overhaul, we will let the slot manage its own sidebar for the dashboard, 
                 or we include a global sidebar here if needed. 
                 Wait, standard Laravel Breeze puts navigation here. We will keep it for now but the dashboard will override it or use it. -->
            <div class="hidden">
                <livewire:layout.navigation />
            </div>

            <!-- Page Content -->
            <main class="flex-1 min-w-0">
                @if (isset($header))
                    <header>
                        {{ $header }}
                    </header>
                @endif
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
