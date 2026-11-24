<?php

namespace App\Http\Controllers;

use App\Models\AppointmentSlot;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function card(Request $request, Booking $booking): Response|View
    {
        $booking->load(['patient', 'clinic.doctorProfile.user', 'slot']);
        $ownsBooking = $request->user()->id === $booking->patient_id;
        $ownsClinic = $request->user()->isApprovedDoctor() && $request->user()->doctorProfile->clinics()->whereKey($booking->clinic_id)->exists();
        abort_unless($ownsBooking || $ownsClinic, 403);
        abort_unless(in_array($booking->status, ['confirmed', 'completed'], true), 404);

        $doctorName = e($booking->clinic->doctorProfile->user->name);
        $attendeeName = e($booking->attendee_name);
        $attendeeDetails = e($booking->attendee_age.' سنة · '.$booking->attendee_country);
        $clinicName = e($booking->clinic->name);
        $time = e($booking->slot->starts_at->timezone($booking->clinic->timezone)->translatedFormat('l j F Y، g:i a'));
        $reference = e($booking->reference);
        $issued = e(now()->timezone($booking->clinic->timezone)->translatedFormat('j F Y'));
        $verificationUrl = e(route('bookings.verify', $booking->reference));
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"1000\" height=\"600\" viewBox=\"0 0 1000 600\" role=\"img\"><rect width=\"1000\" height=\"600\" rx=\"34\" fill=\"#fcfcf7\"/><path d=\"M0 0h1000v115H0z\" fill=\"#1c3a13\"/><text x=\"920\" y=\"74\" fill=\"#d3fa99\" font-size=\"46\" font-family=\"Cairo,Arial\" text-anchor=\"end\">طبي · بطاقة موعد</text><text x=\"850\" y=\"190\" fill=\"#1c3a13\" font-size=\"30\" font-family=\"Cairo,Arial\" text-anchor=\"end\">د. {$doctorName}</text><text x=\"850\" y=\"247\" fill=\"#333\" font-size=\"24\" font-family=\"Cairo,Arial\" text-anchor=\"end\">المريض: {$attendeeName}</text><text x=\"850\" y=\"287\" fill=\"#777\" font-size=\"18\" font-family=\"Cairo,Arial\" text-anchor=\"end\">{$attendeeDetails}</text><text x=\"850\" y=\"330\" fill=\"#333\" font-size=\"24\" font-family=\"Cairo,Arial\" text-anchor=\"end\">الموعد: {$time} (توقيت القاهرة)</text><text x=\"850\" y=\"385\" fill=\"#333\" font-size=\"24\" font-family=\"Cairo,Arial\" text-anchor=\"end\">العيادة: {$clinicName}</text><text x=\"850\" y=\"440\" fill=\"#333\" font-size=\"24\" font-family=\"Cairo,Arial\" text-anchor=\"end\">كود الحجز: {$reference}</text><text x=\"850\" y=\"495\" fill=\"#777\" font-size=\"18\" font-family=\"Cairo,Arial\" text-anchor=\"end\">تاريخ الإصدار: {$issued}</text><text x=\"500\" y=\"555\" fill=\"#9ab078\" font-size=\"24\" opacity=\".35\" font-family=\"Cairo,Arial\" text-anchor=\"middle\">طبي · د. {$doctorName}</text><a href=\"{$verificationUrl}\"><text x=\"90\" y=\"520\" fill=\"#496736\" font-size=\"17\" font-family=\"Arial\">تحقق من صلاحية الحجز</text></a></svg>";

        return response($svg, 200, ['Content-Type' => 'image/svg+xml; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="tebbi-appointment-'.$booking->reference.'.svg"']);
    }

    public function verify(string $reference): View
    {
        $booking = Booking::query()->with(['clinic.doctorProfile.user', 'slot'])->where('reference', $reference)->firstOrFail();
        abort_unless(in_array($booking->status, ['confirmed', 'completed'], true), 404);

        return view('booking-verification', compact('booking'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'patient', 403);
        $data = $request->session()->get('booking_draft');
        abort_unless(is_array($data) && ($data['user_id'] ?? null) === $request->user()->id, 419);

        DB::transaction(function () use ($request, $data): void {
            $slot = AppointmentSlot::query()->whereKey($data['slot_id'])->lockForUpdate()->firstOrFail();
            if (! $slot->is_available || $slot->starts_at->isPast()) {
                throw ValidationException::withMessages(['slot_id' => 'المعاد ده مبقاش متاح، اختاري معاد تاني.']);
            }
            $profile = $slot->clinic->doctorProfile;
            if ($profile->approval_status !== 'approved') {
                abort(404);
            }
            Booking::create([
                'reference' => strtoupper(Str::random(10)), 'patient_id' => $request->user()->id,
                'clinic_id' => $slot->clinic_id, 'appointment_slot_id' => $slot->id, 'status' => 'pending',
                'for_someone_else' => $data['for_someone_else'],
                'attendee_name' => $data['attendee_name'], 'attendee_age' => $data['attendee_age'],
                'attendee_country' => $data['attendee_country'], 'reason' => $data['reason'],
                'payment_method' => 'clinic',
            ]);
            $slot->update(['is_available' => false]);
        });

        $request->session()->forget('booking_draft');

        return redirect()->route('dashboard')->with('success', 'وصل طلبك للعيادة. الموعد لسه مش مؤكد لحد ما الدكتور يرد.');
    }

    public function preview(Request $request): View
    {
        abort_unless($request->user()->role === 'patient', 403);
        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:appointment_slots,id'],
            'for_someone_else' => ['required', 'boolean'],
            'attendee_name' => ['required_if:for_someone_else,1', 'nullable', 'string', 'max:255'],
            'attendee_age' => ['required_if:for_someone_else,1', 'nullable', 'integer', 'min:1', 'max:120'],
            'attendee_country' => ['required_if:for_someone_else,1', 'nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $slot = AppointmentSlot::query()->with(['clinic.doctorProfile.user'])->whereKey($data['slot_id'])
            ->where('is_available', true)->where('starts_at', '>', now())->firstOrFail();
        abort_unless($slot->clinic->doctorProfile->approval_status === 'approved', 404);
        $isForSomeoneElse = (bool) $data['for_someone_else'];
        $attendee = $isForSomeoneElse ? [
            'attendee_name' => $data['attendee_name'],
            'attendee_age' => $data['attendee_age'],
            'attendee_country' => $data['attendee_country'],
        ] : [
            'attendee_name' => $request->user()->name,
            'attendee_age' => $request->user()->age,
            'attendee_country' => $request->user()->country,
        ];
        $draft = [
            'user_id' => $request->user()->id,
            'slot_id' => $slot->id,
            'for_someone_else' => $isForSomeoneElse,
            ...$attendee,
            'reason' => $data['reason'] ?? null,
        ];
        $request->session()->put('booking_draft', $draft);

        return view('booking-summary', ['slot' => $slot, 'draft' => $draft]);
    }

    public function decide(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($request->user()->role === 'doctor', 403);
        $data = $request->validate(['decision' => ['required', 'in:accepted,declined,completed']]);
        $ownsClinic = $request->user()->doctorProfile->clinics()->whereKey($booking->clinic_id)->exists();
        abort_unless($ownsClinic, 403);

        DB::transaction(function () use ($booking, $data): void {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($data['decision'] === 'completed') {
                if ($locked->status !== 'confirmed') {
                    throw ValidationException::withMessages(['decision' => 'ماينفعش نكمل الزيارة قبل تأكيد الحجز.']);
                }
                $locked->update(['status' => 'completed', 'decided_at' => now()]);

                return;
            }
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'تم التعامل مع الطلب ده قبل كده.']);
            }
            if ($data['decision'] === 'accepted') {
                Clinic::query()->whereKey($locked->clinic_id)->lockForUpdate()->firstOrFail();
                $slot = AppointmentSlot::query()->whereKey($locked->appointment_slot_id)->lockForUpdate()->firstOrFail();
                $conflict = Booking::query()->join('appointment_slots', 'bookings.appointment_slot_id', '=', 'appointment_slots.id')
                    ->where('bookings.clinic_id', $locked->clinic_id)
                    ->where('bookings.status', 'confirmed')
                    ->where('appointment_slots.starts_at', '<', $slot->ends_at)
                    ->where('appointment_slots.ends_at', '>', $slot->starts_at)
                    ->exists();
                if ($conflict) {
                    throw ValidationException::withMessages(['decision' => 'المعاد اتأكد لحجز تاني.']);
                }
                $locked->update(['status' => 'confirmed', 'decided_at' => now()]);
            } else {
                $locked->update(['status' => 'declined', 'decided_at' => now()]);
                $locked->slot()->update(['is_available' => true]);
            }
        });

        return back()->with('success', 'تم تحديث حالة الحجز.');
    }

    public function addSlot(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);
        $startsAt = Carbon::createFromFormat('Y-m-d\\TH:i', $data['starts_at'], 'Africa/Cairo')->utc();
        $endsAt = Carbon::createFromFormat('Y-m-d\\TH:i', $data['ends_at'], 'Africa/Cairo')->utc();
        if (! $endsAt->greaterThan($startsAt)) {
            throw ValidationException::withMessages(['ends_at' => 'وقت الانتهاء لازم يكون بعد وقت بداية الموعد.']);
        }
        if (! $startsAt->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'اختاري وقت بداية قادمًا بتوقيت القاهرة؛ وقت البداية الذي اخترتيه مضى بالفعل.']);
        }
        DB::transaction(function () use ($request, $data, $startsAt, $endsAt): void {
            $clinic = Clinic::query()->whereKey($data['clinic_id'])->lockForUpdate()->firstOrFail();
            abort_unless($request->user()->doctorProfile->clinics()->whereKey($clinic->id)->exists(), 403);
            $overlap = AppointmentSlot::where('clinic_id', $clinic->id)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_at' => 'المعاد بيتعارض مع ميعاد موجود.']);
            }
            AppointmentSlot::create(['clinic_id' => $clinic->id, 'starts_at' => $startsAt, 'ends_at' => $endsAt]);
        });

        return back()->with('success', 'اتضاف المعاد المتاح بتوقيت القاهرة.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->patient_id === $request->user()->id, 403);
        DB::transaction(function () use ($booking): void {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['booking' => 'الإلغاء الذاتي متاح قبل رد العيادة فقط. تواصلي مع العيادة لتغيير المواعيد المؤكدة.']);
            }
            $locked->update(['status' => 'cancelled', 'decided_at' => now()]);
            $locked->slot()->update(['is_available' => true]);
        });

        return back()->with('success', 'تم إلغاء طلب الحجز.');
    }

    public function review(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->patient_id === $request->user()->id, 403);
        abort_unless($booking->status === 'completed', 403);
        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5'], 'comment' => ['required', 'string', 'min:3', 'max:1000']]);
        Review::create([
            'booking_id' => $booking->id,
            'doctor_profile_id' => $booking->clinic->doctor_profile_id,
            'patient_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);

        return back()->with('success', 'شكرًا على تقييمك.');
    }
}
