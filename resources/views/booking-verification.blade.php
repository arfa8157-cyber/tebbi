@extends('layouts.app')
@section('title', 'التحقق من الحجز — طبي')
@section('content')
<section class="section container"><div class="panel verification-card"><span class="verified-pill">✓ حجز صالح</span><h1>الحجز مؤكد في نظام طبي</h1><p>د. {{ $booking->clinic->doctorProfile->user->name }} · {{ $booking->clinic->name }}</p><p>الموعد: {{ $booking->slot->starts_at->timezone($booking->clinic->timezone)->translatedFormat('l j F Y، g:i a') }} · توقيت القاهرة</p><p>الحالة: {{ $booking->status === 'completed' ? 'الزيارة مكتملة' : 'مؤكد' }}</p><small>كود الحجز: {{ $booking->reference }}</small><div class="integration-note">صفحة تحقق عامة؛ معلومات المريض الخاصة غير معروضة.</div></div></section>
@endsection
