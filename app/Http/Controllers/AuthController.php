<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth', ['mode' => 'login']);
    }

    public function showRegister(): View
    {
        return view('auth', ['mode' => 'register']);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^\S+(?:\s+\S+){2,}$/u', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^01[0125][0-9]{8}$/', 'unique:users,phone'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'country' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'account_type' => ['required', 'in:patient,doctor'],
            'specialty' => ['required_if:account_type,doctor', 'nullable', 'string', 'max:120'],
            'bio' => ['required_if:account_type,doctor', 'nullable', 'string', 'max:3000'],
            'clinic_name' => ['required_if:account_type,doctor', 'nullable', 'string', 'max:150'],
            'address' => ['required_if:account_type,doctor', 'nullable', 'string', 'max:255'],
            'consultation_fee' => ['required_if:account_type,doctor', 'nullable', 'integer', 'min:1', 'max:100000'],
            'credential' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], ['phone.regex' => 'اكتبي رقم موبايل مصري صحيح يبدأ بـ 010 أو 011 أو 012 أو 015.']);

        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
            'age' => $data['age'], 'country' => $data['country'], 'password' => $data['password'],
            'role' => $data['account_type'],
        ]);

        if ($data['account_type'] === 'doctor') {
            $profile = DoctorProfile::create([
                'user_id' => $user->id, 'specialty' => $data['specialty'], 'bio' => $data['bio'],
                'document_path' => $request->file('credential')?->store('doctor-credentials'),
            ]);
            Clinic::create([
                'doctor_profile_id' => $profile->id, 'name' => $data['clinic_name'],
                'address' => $data['address'], 'consultation_fee' => $data['consultation_fee'],
            ]);
        }

        Auth::login($user);
        $user->sendEmailVerificationNotification();
        $request->session()->regenerate();

        $message = config('services.tebbi.require_verified_email')
            ? 'الحساب اتعمل. أكدي بريدك الإلكتروني عشان تكملي استخدام طبي.'
            : 'الحساب اتعمل، تقدري تكملي تجربة الموقع على جهازك.';

        return redirect()->route('dashboard')->with('success', $message);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'محاولات كتير، جربي تاني بعد دقيقة.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember')) || $request->user()?->is_suspended) {
            Auth::logout();
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'تم تسجيل الدخول.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgotPassword(): View
    {
        return view('auth', ['mode' => 'forgot']);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'لو البريد مسجل عندنا، هيوصلك رابط تغيير كلمة المرور.');
    }

    public function showResetPassword(string $token, Request $request): View
    {
        return view('auth', ['mode' => 'reset', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'], 'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
            $user->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'كلمة المرور اتغيرت. سجّل دخولك بكلمة المرور الجديدة.');
        }

        return back()->withErrors(['email' => 'تعذر تغيير كلمة المرور. اطلبي رابط جديد وجربي تاني.']);
    }
}
