<?php

use App\Models\OtpCode;
use App\Notifications\SendOtpNotification;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $code = '';
    public string $message = '';

    public function mount(): void
    {
        // Automatically send OTP code on page load if one doesn't exist
        $this->sendCode();
    }

    public function sendCode(): void
    {
        $user = auth()->user();

        if (!$user) {
            $this->redirect(route('login'));
            return;
        }

        if (empty($user->phone)) {
            $this->message = 'لا يوجد رقم هاتف مسجل لحسابك. يرجى الاتصال بالمسؤول.';
            return;
        }

        // Check for existing valid OTP
        $existing = OtpCode::where('user_id', $user->id)
            ->where('type', 'login_2fa')
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existing) {
            $otp = $existing->code;
        } else {
            $otp = (string) random_int(100000, 999999);
            OtpCode::create([
                'user_id' => $user->id,
                'code' => $otp,
                'type' => 'login_2fa',
                'expires_at' => Carbon::now()->addMinutes(5),
                'is_used' => false,
            ]);
        }

        // Send via WhatsApp
        try {
            $user->notify(new SendOtpNotification($otp));
            $this->message = 'تم إرسال رمز التحقق إلى رقم واتساب الخاص بك بنجاح.';
            if (app()->environment('local')) {
                $this->message .= ' (الرمز المحلي للتطوير: ' . $otp . ')';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send OTP to user {$user->id}: " . $e->getMessage());
            $this->message = 'حدث خطأ أثناء إرسال الرمز.';
            if (app()->environment('local')) {
                $this->message .= ' (الرمز للتطوير: ' . $otp . ')';
            }
        }
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('login'));
    }

    public function verify(): void
    {
        $this->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق.',
            'code.digits' => 'يجب أن يتكون الرمز من 6 أرقام.',
        ]);

        $user = auth()->user();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $this->code)
            ->where('type', 'login_2fa')
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            $this->addError('code', 'رمز التحقق غير صحيح أو منتهي الصلاحية.');
            return;
        }

        // Mark OTP as used and set session verified
        $otp->update(['is_used' => true]);
        session(['2fa_verified' => true]);
        session(['last_activity_timestamp' => time()]);

        // Redirect based on role
        if ($user->role === 'admin') {
            $this->redirect(route('admin.dashboard'), navigate: true);
        } else {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }
}; ?>

<div dir="rtl" class="font-sans">
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-gray-800">تأكيد الهوية</h2>
        <p class="text-sm text-gray-600 mt-2">
            لقد قمنا بإرسال رمز تحقق مكون من 6 أرقام إلى رقم الواتساب الخاص بك:
            <span class="font-bold text-indigo-600 block dir-ltr mt-1">
                {{ auth()->user()->phone ?? 'غير مسجل' }}
            </span>
        </p>
    </div>

    @if ($message)
        <div class="mb-4 p-3 bg-blue-50 text-blue-800 text-xs rounded-lg text-center border border-blue-100">
            {{ $message }}
        </div>
    @endif

    <form wire:submit="verify">
        <!-- Verification Code -->
        <div>
            <x-input-label for="code" :value="__('رمز التحقق (OTP)')" class="text-right block mb-1" />
            <x-text-input wire:model="code" id="code" class="block mt-1 w-full text-center tracking-widest text-xl font-bold" 
                          type="text" name="code" required autofocus autocomplete="off" placeholder="------" maxlength="6" />
            <x-input-error :messages="$errors->get('code')" class="mt-2 text-right" />
        </div>

        <div class="mt-6 flex flex-col space-y-3">
            <x-primary-button class="w-full justify-center bg-red-600 hover:bg-red-700 active:bg-red-800 focus:ring-red-500">
                {{ __('تأكيد الرمز') }}
            </x-primary-button>
            
            <div x-data="{ timer: 30, interval: null }" 
                 x-init="interval = setInterval(() => { if (timer > 0) timer-- }, 1000)" 
                 class="text-center">
                <button type="button" 
                        wire:click="sendCode" 
                        x-bind:disabled="timer > 0"
                        x-on:click="timer = 30"
                        class="text-sm text-indigo-600 hover:text-indigo-900 disabled:opacity-50 disabled:cursor-not-allowed underline">
                    إعادة إرسال الرمز
                </button>
                <span x-show="timer > 0" class="text-xs text-gray-500 block mt-1">
                    يمكنك إعادة الإرسال بعد <span x-text="timer"></span> ثانية
                </span>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                <button type="button" wire:click="logout" class="text-sm font-bold text-rose-600 hover:text-rose-800 transition">
                    تسجيل الخروج والعودة
                </button>
            </div>
        </div>
    </form>
</div>
