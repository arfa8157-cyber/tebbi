<!doctype html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'طبي — رعايتك أقرب')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#1c3a13">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="site-header"><div class="container nav-wrap">
    <a class="brand" href="{{ route('home') }}"><span class="brand-mark">ط</span><span>طبي<small>صحتك في مكانها</small></span></a>
    <nav class="main-nav"><a href="{{ route('doctors.index') }}">دور على دكتور</a><a href="{{ route('home') }}#specialties">التخصصات</a><a href="{{ route('home') }}#how">إزاي بنساعدك؟</a></nav>
    <div class="nav-actions"><button class="theme-toggle" type="button" aria-label="تغيير المظهر">◐</button>
        @auth
            <a class="button button-soft" href="{{ route('dashboard') }}">حسابي</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">خروج</button></form>
        @else
            <a class="text-button" href="{{ route('login') }}">دخول</a><a class="button button-primary button-small" href="{{ route('register') }}">حساب جديد</a>
        @endauth
    </div>
    <button class="mobile-menu" aria-label="القائمة">☰</button>
</div></header>
<main>@if(session('success'))<div class="container"><div class="notice success">{{ session('success') }}</div></div>@endif @yield('content')</main>
<footer class="site-footer"><div class="container footer-wrap"><a class="brand" href="{{ route('home') }}"><span class="brand-mark">ط</span><span>طبي<small>رعاية أقرب ليك</small></span></a><p>خلي خطوة الاطمئنان أسهل.</p><nav><a href="{{ route('privacy') }}">الخصوصية</a><a href="{{ route('terms') }}">الشروط</a><a href="{{ route('cancellation') }}">سياسة الإلغاء</a></nav><small>© {{ date('Y') }} طبي. كل الحقوق محفوظة.</small></div></footer>
</body></html>
