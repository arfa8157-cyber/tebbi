@extends('layouts.app')
@section('title', ['login' => 'تسجيل الدخول — طبي', 'register' => 'إنشاء حساب — طبي', 'forgot' => 'استعادة كلمة المرور — طبي', 'reset' => 'تغيير كلمة المرور — طبي'][$mode])
@section('content')
<section class="auth-page container"><div class="auth-aside"><span class="brand-mark large-mark">ط</span><span class="eyebrow">خطوة صغيرة لراحة أكبر</span><h1>{{ $mode === 'login' ? 'أهلًا بيك تاني' : 'خلّينا نبدأ' }}</h1><p>{{ $mode === 'login' ? 'سجّل دخولك وتابع مواعيدك وطلبات الحجز.' : 'حساب واحد يسهّل عليك تدور وتحجز وتتابع كل مواعيدك.' }}</p><div class="auth-note">♡<span>بياناتك الصحية والشخصية خاصة ومحمية.</span></div></div>
<div class="auth-form-card">@if($errors->any())<div class="form-errors"><strong>راجعي البيانات دي:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<div class="form-heading"><h2>{{ ['login' => 'تسجيل الدخول', 'register' => 'حساب جديد', 'forgot' => 'استعادة كلمة المرور', 'reset' => 'كلمة مرور جديدة'][$mode] }}</h2><p>@if($mode === 'login')لسه معندكش حساب؟ <a href="{{ route('register') }}">إنشاء حساب</a>@elseif($mode === 'register')عندك حساب بالفعل؟ <a href="{{ route('login') }}">سجّل دخولك</a>@else<a href="{{ route('login') }}">العودة لتسجيل الدخول</a>@endif</p></div>
@if($mode === 'login')
<form method="POST" action="{{ route('login.store') }}" class="form-grid">@csrf
    <label class="field full">البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<small class="error">{{ $message }}</small>@enderror</label>
    <label class="field full">كلمة المرور<input type="password" name="password" autocomplete="current-password" required>@error('password')<small class="error">{{ $message }}</small>@enderror</label>
    <a class="text-link full forgot-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
    <label class="check-field full"><input type="checkbox" name="remember" value="1"> افتكرني على الجهاز ده</label>
    <button class="button button-primary full" type="submit">دخول لحسابي <span>←</span></button>
</form>
@elseif($mode === 'register')
<form method="POST" action="{{ route('register.store') }}" class="form-grid" enctype="multipart/form-data" id="register-form">@csrf
    <label class="field full">نوع الحساب<select name="account_type" id="account-type"><option value="patient" @selected(old('account_type', request('type')) !== 'doctor')>مريض / مستخدم</option><option value="doctor" @selected(old('account_type', request('type')) === 'doctor')>دكتور — طلب انضمام</option></select></label>
    <label class="field full">الاسم الثلاثي<input name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<small class="error">{{ $message }}</small>@enderror</label>
    <label class="field">رقم الموبايل المصري<input name="phone" value="{{ old('phone') }}" placeholder="01012345678" inputmode="tel" required>@error('phone')<small class="error">{{ $message }}</small>@enderror</label>
    <label class="field">السن<input name="age" type="number" min="1" max="120" value="{{ old('age') }}" required>@error('age')<small class="error">{{ $message }}</small>@enderror</label>
    <label class="field full">البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" required>@error('email')<small class="error">{{ $message }}</small>@enderror</label>
    <label class="field full">البلد<input name="country" value="{{ old('country', 'مصر') }}" placeholder="اكتب بلدك" required>@error('country')<small class="error">{{ $message }}</small>@enderror</label>
    <div class="doctor-fields full" id="doctor-fields" @if(old('account_type', request('type')) !== 'doctor') hidden @endif>
        <div class="form-grid"><label class="field">التخصص<input name="specialty" value="{{ old('specialty') }}"></label><label class="field">سعر الكشف بالجنيه<input type="number" name="consultation_fee" min="1" value="{{ old('consultation_fee') }}"></label>
        <label class="field full">نبذة مهنية<textarea name="bio" rows="3">{{ old('bio') }}</textarea></label><label class="field">اسم العيادة<input name="clinic_name" value="{{ old('clinic_name') }}"></label><label class="field">العنوان<input name="address" value="{{ old('address') }}"></label>
        <label class="field full">مستند مهني للمراجعة (PDF أو صورة، حتى ٥ ميجابايت)<input type="file" name="credential" accept=".pdf,.jpg,.jpeg,.png"></label></div>
        <p class="hint">حساب الدكتور يفضل «قيد المراجعة» لحد ما الإدارة تراجع بياناتك.</p>
    </div>
    <label class="field">كلمة المرور<input type="password" name="password" minlength="10" autocomplete="new-password" required>@error('password')<small class="error">{{ $message }}</small>@enderror</label><label class="field">تأكيد كلمة المرور<input type="password" name="password_confirmation" minlength="10" required></label>
    <button class="button button-primary full" type="submit">إنشاء الحساب <span>←</span></button>
</form>
@elseif($mode === 'forgot')
<form method="POST" action="{{ route('password.email') }}" class="form-grid">@csrf
    <p class="full muted">اكتبي بريدك، ولو الحساب موجود هنبعتلك رابط آمن لتغيير كلمة المرور.</p>
    <label class="field full">البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" required>@error('email')<small class="error">{{ $message }}</small>@enderror</label>
    <button class="button button-primary full">إرسال رابط التغيير <span>←</span></button>
</form>
@else
<form method="POST" action="{{ route('password.update') }}" class="form-grid">@csrf
    <input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="email" value="{{ old('email', $email) }}">
    <label class="field full">كلمة المرور الجديدة<input type="password" name="password" minlength="10" autocomplete="new-password" required>@error('password')<small class="error">{{ $message }}</small>@enderror</label>
    <label class="field full">تأكيد كلمة المرور<input type="password" name="password_confirmation" minlength="10" required></label>
    <button class="button button-primary full">تغيير كلمة المرور</button>
</form>
@endif</div></section>
@endsection
