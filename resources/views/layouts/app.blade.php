<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', __('messages.transfer_system')) }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo.png?v=2') }}">

        <!-- PWA Meta Tags -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#f8f9fc">
        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
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
        
        @stack('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                window.addEventListener('new-notification-toast', event => {
                    const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: detail.title || 'إشعار جديد',
                        text: detail.message || '',
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'bg-white text-slate-800 shadow-xl border border-slate-100 rounded-2xl'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });
                });

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
