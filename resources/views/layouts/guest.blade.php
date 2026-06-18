<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'نظام الحوالات') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo.png?v=2') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-[#f8f9fc] min-h-screen" style="font-family: 'Cairo', sans-serif;">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden">
            
            <!-- Decorative Background Elements from the modern look -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-40 -z-10">
                <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-gradient-to-br from-primary-400 to-indigo-500 blur-[100px] animate-pulse" style="animation-duration: 8s;"></div>
                <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] rounded-full bg-gradient-to-br from-pink-400 to-orange-400 blur-[80px]"></div>
                <!-- Triangles -->
                <div class="absolute top-20 left-20 text-primary-300 transform rotate-45 text-2xl">▲</div>
                <div class="absolute bottom-32 right-32 text-pink-300 transform -rotate-12 text-3xl">▲</div>
            </div>

            <div class="flex justify-center mb-6">
                <img src="{{ asset('logo.png?v=2') }}" alt="Teacher VC" class="h-28 object-contain">
            </div>

            <div class="w-full sm:max-w-md mt-2 px-10 py-10 bg-white shadow-soft-xl border border-slate-50 sm:rounded-[32px] relative z-10">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
