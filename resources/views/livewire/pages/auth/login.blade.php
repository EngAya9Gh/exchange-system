<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-black text-slate-800">
            {{ __('تسجيل الدخول') }}
        </h2>
        <p class="text-sm text-slate-500 mt-2">مرحباً بك مجدداً في نظام SxDx</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" action="{{ route('login') }}" method="POST" onsubmit="event.preventDefault();">
        @csrf
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('البريد الإلكتروني / رقم الجوال')" class="text-slate-700 font-bold" />
            <input wire:model="form.email" id="email" class="block mt-2 w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" type="text" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-rose-500" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <div class="flex justify-between items-center mb-2">
                <x-input-label for="password" :value="__('كلمة المرور')" class="text-slate-700 font-bold mb-0" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-primary-600 hover:text-primary-700 transition" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('نسيت الكلمة؟') }}
                    </a>
                @endif
            </div>
            <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password" class="block w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-rose-500" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-5">
            <label for="remember" class="inline-flex items-center cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-primary-600 shadow-sm focus:ring-primary-500 w-5 h-5" name="remember">
                <span class="ms-2 text-sm font-bold text-slate-600">{{ __('تذكرني في المرات القادمة') }}</span>
            </label>
        </div>

        <div class="mt-8">
            <button type="submit" class="w-full py-4 bg-gradient-to-r from-primary-600 to-indigo-600 hover:from-primary-700 hover:to-indigo-700 text-white rounded-xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1">
                {{ __('تسجيل الدخول') }}
            </button>
        </div>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-slate-500 font-bold">ليس لديك حساب؟ <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 transition" wire:navigate>سجل الآن</a></p>
        </div>
    </form>
</div>
