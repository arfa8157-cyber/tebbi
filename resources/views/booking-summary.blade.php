@extends('layouts.app')
@section('title', 'مراجعة طلب الحجز — طبي')
@section('content')
<section class="section container"><div class="booking-summary"><div class="summary-heading"><span class="eyebrow">راجعي التفاصيل قبل التأكيد</span><h1>ملخص طلب الحجز</h1><p>إرسال الطلب مش معناه إن الموعد اتأكد. العيادة هتراجع طلبك وترد عليك.</p></div>
    <div class="panel summary-card"><div class="summary-doctor"><span class="doctor-avatar">{{ mb_substr($slot->clinic->doctorProfile->user->name, 0, 1) }}</span><div><small>الدكتور</small><strong>{{ $slot->clinic->doctorProfile->user->name }}</strong><span>{{ $slot->clinic->doctorProfile->specialty }}</span></div></div>
        <dl class="summary-details"><div><dt>العيادة</dt><dd>{{ $slot->clinic->name }}</dd></div><div><dt>العنوان</dt><dd>{{ $slot->clinic->address }}</dd></div><div><dt>المعاد</dt><dd>{{ $slot->starts_at->timezone($slot->clinic->timezone)->translatedFormat('l j F Y، g:i a') }} · توقيت القاهرة</dd></div><div><dt>الحجز لـ</dt><dd>{{ $draft['attendee_name'] }} · {{ $draft['attendee_age'] }} سنة · {{ $draft['attendee_country'] }}</dd></div><div><dt>سعر الكشف</dt><dd>{{ number_format($slot->clinic->consultation_fee) }} جنيه</dd></div><div><dt>طريقة الدفع</dt><dd>في العيادة</dd></div>@if($draft['reason'])<div class="summary-reason"><dt>سبب الزيارة (خاص)</dt><dd>{{ $draft['reason'] }}</dd></div>@endif</dl>
        <div class="summary-notice">حالة الطلب بعد الإرسال: <strong>بانتظار رد العيادة</strong></div>
        <form method="POST" action="{{ route('bookings.store') }}">@csrf<button class="button button-primary full">تأكيد وإرسال الطلب <span>←</span></button></form>
        <a class="summary-back" href="{{ route('doctors.show', $slot->clinic->doctor_profile_id) }}">رجوع وتعديل البيانات</a>
    </div></div></section>
@endsection
