<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-black text-slate-800">
            {{ __('تسجيل حساب جديد') }}
        </h2>
        <p class="text-sm text-slate-500 mt-2">انضم إلى شبكة SxDx للحوالات المالية</p>
    </div>

    <form wire:submit="register" action="{{ route('register') }}" method="POST" onsubmit="event.preventDefault();">
        @csrf
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('الاسم الكامل')" class="text-slate-700 font-bold" />
            <input wire:model="name" id="name" class="block mt-2 w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-rose-500" />
        </div>

        <!-- Email Address -->
        <div class="mt-5">
            <x-input-label for="email" :value="__('البريد الإلكتروني')" class="text-slate-700 font-bold" />
            <input wire:model="email" id="email" class="block mt-2 w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" type="email" name="email" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-500" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" :value="__('كلمة المرور')" class="text-slate-700 font-bold" />
            <input wire:model="password" id="password" class="block mt-2 w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-500" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-5">
            <x-input-label for="password_confirmation" :value="__('تأكيد كلمة المرور')" class="text-slate-700 font-bold" />
            <input wire:model="password_confirmation" id="password_confirmation" class="block mt-2 w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-rose-500" />
        </div>

        <div class="mt-8">
            <button type="submit" class="w-full py-4 bg-gradient-to-r from-primary-600 to-indigo-600 hover:from-primary-700 hover:to-indigo-700 text-white rounded-xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1">
                {{ __('إنشاء الحساب') }}
            </button>
        </div>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-slate-500 font-bold">لديك حساب بالفعل؟ <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 transition" wire:navigate>تسجيل الدخول</a></p>
        </div>
    </form>
</div>
