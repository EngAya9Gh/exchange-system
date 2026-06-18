<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OtpCode;
use App\Notifications\SendOtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpVerificationController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Send OTP if there is no valid existing one
        $existing = OtpCode::where('user_id', $user->id)
            ->where('type', 'login_2fa')
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$existing && !empty($user->telegram_chat_id)) {
            $this->sendCode($user);
        }

        if (empty($user->telegram_chat_id) && empty($user->telegram_link_token)) {
            $user->telegram_link_token = \Illuminate\Support\Str::random(32);
            $user->save();
        }

        $botUsername = config('services.telegram.bot_username', 'exchange_adbtrk_bot');

        return view('auth.verify-otp', compact('botUsername'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق.',
            'code.digits' => 'يجب أن يتكون الرمز من 6 أرقام.',
        ]);

        $user = auth()->user();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->where('type', 'login_2fa')
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        // Mark OTP as used and set session verified
        $otp->update(['is_used' => true]);
        session(['2fa_verified' => true]);
        session(['last_activity_timestamp' => time()]);

        // Redirect based on role
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function resend(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (empty($user->telegram_chat_id)) {
            return back()->with('error', 'يجب ربط حسابك ببوت التلغرام أولاً لتتمكن من استلام الرمز.');
        }

        $this->sendCode($user);

        return back()->with('status', 'تم إعادة إرسال رمز التحقق إلى حساب التلغرام الخاص بك بنجاح.');
    }

    private function sendCode($user)
    {
        // Invalidate old unused OTPs
        OtpCode::where('user_id', $user->id)
            ->where('type', 'login_2fa')
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $otp = (string) random_int(100000, 999999);
        OtpCode::create([
            'user_id' => $user->id,
            'code' => $otp,
            'type' => 'login_2fa',
            'expires_at' => Carbon::now()->addMinutes(5),
            'is_used' => false,
        ]);

        try {
            $user->notify(new SendOtpNotification($otp));
            if (app()->environment('local')) {
                session()->flash('dev_code', $otp);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to user {$user->id}: " . $e->getMessage());
            if (app()->environment('local')) {
                session()->flash('dev_code', $otp);
            }
        }
    }

    public function checkStatus()
    {
        return response()->json([
            'linked' => !empty(auth()->user()->telegram_chat_id)
        ]);
    }
}
