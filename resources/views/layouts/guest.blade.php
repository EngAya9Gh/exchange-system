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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="font-sans text-slate-900 antialiased bg-[#f8f9fc] min-h-screen" style="font-family: 'Cairo', sans-serif;">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden bg-slate-50/50">
            
            <!-- Decorative Glows (Far Back) -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none -z-20">
                <div class="absolute top-[0%] left-[-5%] w-[30%] h-[30%] rounded-full bg-gradient-to-br from-red-400 to-rose-300 blur-[80px] opacity-70 animate-pulse" style="animation-duration: 10s;"></div>
                <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-gradient-to-br from-pink-400 to-orange-400 blur-[80px] opacity-80"></div>
            </div>

            <!-- Full Screen Glass Layer -->
            <div class="absolute inset-0 bg-white/1 backdrop-blur-[20px] -z-10 pointer-events-none border-t border-white/50"></div>

            <div class="flex justify-center mb-6 relative z-20">
                <img src="{{ asset('logo.png?v=2') }}" alt="Teacher VC" class="h-28 object-contain drop-shadow-md">
            </div>

            <!-- Inner form container with subtle borders -->
            <div class="w-full sm:max-w-md mt-2 px-10 py-10 bg-white/50 shadow-[0_8px_32px_rgba(0,0,0,0.05)] border border-white/60 sm:rounded-[32px] relative z-10">
                {{ $slot }}
            </div>
        </div>
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 500) {
                            preventDefault();
                            Swal.fire({
                                icon: 'error',
                                title: "{{ __('messages.system_error') }}",
                                text: "{{ __('messages.system_error_description') }}",
                                confirmButtonText: "{{ __('messages.ok') }}",
                                confirmButtonColor: '#3b82f6',
                            });
                        } else if (status === 419) {
                            preventDefault();
                            Swal.fire({
                                icon: 'warning',
                                title: "{{ __('messages.session_expired') }}",
                                text: "{{ __('messages.session_expired_description') }}",
                                confirmButtonText: "{{ __('messages.reload_page') }}",
                                confirmButtonColor: '#3b82f6',
                            }).then((result) => {
                                window.location.reload();
                            });
                        } else if (status === 403) {
                            preventDefault();
                            Swal.fire({
                                icon: 'error',
                                title: "{{ __('messages.unauthorized') }}",
                                text: "{{ __('messages.unauthorized_description') }}",
                                confirmButtonText: "{{ __('messages.ok') }}",
                                confirmButtonColor: '#ef4444',
                            });
                        }
                    });
                });
            });
        </script>
    </body>
</html>
