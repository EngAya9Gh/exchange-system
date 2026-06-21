<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($request->only('username', 'password'), $remember)) {
            throw ValidationException::withMessages([
                'username' => 'بيانات الدخول غير صحيحة. يرجى التأكد من اسم المستخدم وكلمة المرور.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->hasAnyRole(['Super Admin', 'Agent'])) {
            return redirect(route('admin.dashboard', absolute: false));
        }

        return redirect(route('dashboard', absolute: false));
    }
}
