@extends('layouts.app')
@section('title', $doctor->user->name.' — طبي')
@section('content')
<section class="page-hero compact-hero"><div class="container"><a class="back-link" href="{{ route('doctors.index') }}">→ كل الدكاترة</a><div class="profile-heading"><span class="doctor-avatar huge-avatar">{{ mb_substr($doctor->user->name, 0, 1) }}</span><div><span class="verified-pill">✓ ملف معتمد</span><h1>{{ $doctor->user->name }}</h1><p>{{ $doctor->specialty }}</p></div></div></div></section>
<section class="section container profile-layout"><div class="profile-main"><article class="panel"><h2>عن الدكتور</h2><p class="long-copy">{{ $doctor->bio }}</p></article>@foreach($doctor->clinics as $clinic)<article class="panel clinic-panel"><div class="panel-title"><div><span class="eyebrow">العيادة</span><h2>{{ $clinic->name }}</h2></div><span class="fee">{{ number_format($clinic->consultation_fee) }} ج.م <small>للكشف</small></span></div><p class="address">⌖ {{ $clinic->address }} · توقيت القاهرة</p>
    <h3>المواعيد المتاحة</h3>@if($clinic->slots->isEmpty())<div class="inline-empty">مفيش مواعيد متاحة حاليًا. ممكن ترجعي تاني قريب.</div>@else
    @auth @if(auth()->user()->role === 'patient')
    <form method="POST" action="{{ route('bookings.preview') }}" class="booking-form">@csrf
        <label class="field">اختاري معادك<select name="slot_id" required><option value="">اختاري وقت مناسب</option>@foreach($clinic->slots as $slot)<option value="{{ $slot->id }}">{{ $slot->starts_at->timezone($clinic->timezone)->translatedFormat('l j F، g:i a') }}</option>@endforeach</select></label>
        <div class="field"><span>الحجز لمين؟</span><div class="choice-row"><label><input type="radio" name="for_someone_else" value="0" checked> ليا</label><label><input type="radio" name="for_someone_else" value="1"> لشخص تاني</label></div></div>
        <div class="other-person-fields" hidden><label class="field">اسم المريض<input name="attendee_name"></label><label class="field">السن<input type="number" min="1" max="120" name="attendee_age"></label><label class="field">البلد<input name="attendee_country"></label></div>
        <label class="field">سبب الزيارة <small>(اختياري — معلومات خاصة)</small><textarea name="reason" rows="3" placeholder="اكتبي وصف مختصر يساعد الدكتور"></textarea></label>
        <div class="payment-note"><span>الدفع في العيادة</span><small>الدفع أونلاين مش متاح حاليًا</small></div>
        @foreach($errors->all() as $error)<small class="error">{{ $error }}</small>@endforeach
        <button class="button button-primary full" type="submit">إرسال طلب الحجز <span>←</span></button><small class="hint">المعاد مش مؤكد إلا بعد موافقة الدكتور.</small>
    </form>
    @else<a class="button button-primary" href="{{ route('dashboard') }}">الحجز متاح لحساب مريض</a>@endif @else<a class="button button-primary" href="{{ route('login') }}">سجّل دخولك للحجز <span>←</span></a>@endauth @endif
</article>@endforeach<article class="panel reviews-panel"><h2>تقييمات المرضى</h2>@forelse($doctor->reviews as $review)<div class="review-item"><strong>★ {{ $review->rating }}/٥ · {{ $review->patient->name }}</strong><p>{{ $review->comment }}</p></div>@empty<div class="inline-empty">مفيش تقييمات منشورة لسه.</div>@endforelse</article></div><aside class="profile-aside"><div class="panel aside-panel"><span class="eyebrow">رعاية مطمّنة</span><h3>اختاري المعاد اللي يناسبك</h3><p>طلب الحجز بيوصل للدكتور، والتأكيد بيظهر عندك بعد الرد.</p><div class="trust-list"><span>✓ مواعيد واضحة</span><span>✓ بياناتك خاصة</span><span>✓ من غير دفع مسبق</span></div></div></aside></section>
@endsection
