<?php

use App\Models\AppointmentSlot;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

function bookingFixture(string $approvalStatus = 'approved'): array
{
    $doctor = User::factory()->create(['role' => 'doctor']);
    $profile = DoctorProfile::create([
        'user_id' => $doctor->id,
        'specialty' => 'باطنة',
        'bio' => 'ملف تجريبي للاختبارات',
        'approval_status' => $approvalStatus,
    ]);
    $clinic = Clinic::create([
        'doctor_profile_id' => $profile->id,
        'name' => 'عيادة الاختبار',
        'address' => 'القاهرة',
        'consultation_fee' => 500,
    ]);
    $slot = AppointmentSlot::create([
        'clinic_id' => $clinic->id,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addMinutes(30),
        'is_available' => true,
    ]);

    return compact('doctor', 'profile', 'clinic', 'slot');
}

it('renders the Arabic public pages and account screens', function (): void {
    $this->get(route('home'))->assertOk()->assertSee('طب', false);
    $this->get(route('doctors.index'))->assertOk()->assertSee('دكاترة معتمدين');
    $this->get(route('login'))->assertOk()->assertSee('تسجيل الدخول');
    $this->get(route('register'))->assertOk()->assertSee('الاسم الثلاثي');
    $this->get(route('privacy'))->assertOk()->assertSee('بياناتك الشخصية');
});

it('creates a patient account, sends verification, and supports password login', function (): void {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'مريم أحمد علي',
        'email' => 'mariam@example.test',
        'phone' => '01012345678',
        'age' => 33,
        'country' => 'مصر',
        'password' => 'secure-Pass-2026',
        'password_confirmation' => 'secure-Pass-2026',
        'account_type' => 'patient',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'mariam@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', ['email' => 'mariam@example.test', 'role' => 'patient']);
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get(route('dashboard'))->assertOk()->assertSee('البريد الإلكتروني لسه مش مؤكد');

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->post(route('login.store'), [
        'email' => 'mariam@example.test',
        'password' => 'secure-Pass-2026',
    ])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('renders all three role dashboard variants', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('طلبات الدكاترة');

    $pendingDoctor = bookingFixture('pending')['doctor'];
    $this->actingAs($pendingDoctor)->get(route('dashboard'))->assertOk()->assertSee('طلبك قيد المراجعة');

    $approvedDoctor = bookingFixture()['doctor'];
    $this->actingAs($approvedDoctor)->get(route('dashboard'))->assertOk()->assertSee('ضيف ميعاد متاح');

    $patient = User::factory()->create(['role' => 'patient']);
    $this->actingAs($patient)->get(route('dashboard'))->assertOk()->assertSee('مفيش حجوزات لسه');
});

it('restricts booking management to administrators', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $patient = User::factory()->create(['role' => 'patient']);

    $this->actingAs($admin)->get(route('admin.bookings'))->assertOk()->assertSee('طلبات ومواعيد العيادات');
    $this->actingAs($patient)->get(route('admin.bookings'))->assertForbidden();
});

it('does not grant a pending applicant doctor permissions', function (): void {
    $fixture = bookingFixture('pending');

    $this->actingAs($fixture['doctor'])
        ->post(route('slots.store'), [
            'clinic_id' => $fixture['clinic']->id,
            'starts_at' => now()->addDays(4)->format('Y-m-d\\TH:i'),
            'ends_at' => now()->addDays(4)->addMinutes(30)->format('Y-m-d\\TH:i'),
        ])
        ->assertForbidden();
});

it('lets a patient submit a request using their profile and holds the selected slot', function (): void {
    $fixture = bookingFixture();
    $patient = User::factory()->create(['role' => 'patient', 'age' => 31, 'country' => 'مصر']);

    $this->actingAs($patient)->post(route('bookings.preview'), [
        'slot_id' => $fixture['slot']->id,
        'for_someone_else' => '0',
        'reason' => 'متابعة دورية',
    ])->assertOk()->assertSee('ملخص طلب الحجز');

    $this->post(route('bookings.store'))->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('bookings', [
        'patient_id' => $patient->id,
        'appointment_slot_id' => $fixture['slot']->id,
        'status' => 'pending',
        'attendee_name' => $patient->name,
        'reason' => 'متابعة دورية',
    ]);
    expect($fixture['slot']->fresh()->is_available)->toBeFalse();
});

it('does not allow another patient to view a confirmed appointment card', function (): void {
    $fixture = bookingFixture();
    $owner = User::factory()->create(['role' => 'patient', 'age' => 30, 'country' => 'مصر']);
    $otherPatient = User::factory()->create(['role' => 'patient']);
    $booking = Booking::create([
        'reference' => 'TEBBI12345',
        'patient_id' => $owner->id,
        'clinic_id' => $fixture['clinic']->id,
        'appointment_slot_id' => $fixture['slot']->id,
        'status' => 'confirmed',
        'attendee_name' => $owner->name,
        'attendee_age' => $owner->age,
        'attendee_country' => $owner->country,
        'payment_method' => 'clinic',
    ]);

    $this->actingAs($otherPatient)->get(route('bookings.card', $booking))->assertForbidden();
});

it('does not allow public registration to create an administrator', function (): void {
    $this->post(route('register.store'), ['account_type' => 'admin'])
        ->assertSessionHasErrors('account_type');

    expect(User::where('role', 'admin')->count())->toBe(0);
});

it('allows only a completed booking owner to submit a review', function (): void {
    $fixture = bookingFixture();
    $patient = User::factory()->create(['role' => 'patient', 'age' => 28, 'country' => 'مصر']);
    $booking = Booking::create([
        'reference' => 'TEBBI54321',
        'patient_id' => $patient->id,
        'clinic_id' => $fixture['clinic']->id,
        'appointment_slot_id' => $fixture['slot']->id,
        'status' => 'pending',
        'attendee_name' => $patient->name,
        'attendee_age' => $patient->age,
        'attendee_country' => $patient->country,
        'payment_method' => 'clinic',
    ]);

    $this->actingAs($patient)->post(route('bookings.review', $booking), ['rating' => 5, 'comment' => 'تجربة ممتازة'])->assertForbidden();

    $this->actingAs($fixture['doctor'])->post(route('bookings.decide', $booking), ['decision' => 'accepted'])->assertRedirect();
    $this->actingAs($fixture['doctor'])->post(route('bookings.decide', $booking), ['decision' => 'completed'])->assertRedirect();
    $this->actingAs($patient)->post(route('bookings.review', $booking), ['rating' => 5, 'comment' => 'تجربة ممتازة'])->assertRedirect();

    $this->assertDatabaseHas('reviews', ['booking_id' => $booking->id, 'patient_id' => $patient->id, 'rating' => 5]);
});

it('lets a patient cancel a pending request and releases its slot', function (): void {
    $fixture = bookingFixture();
    $patient = User::factory()->create(['role' => 'patient', 'age' => 28, 'country' => 'مصر']);
    $booking = Booking::create([
        'reference' => 'TEBBI77777',
        'patient_id' => $patient->id,
        'clinic_id' => $fixture['clinic']->id,
        'appointment_slot_id' => $fixture['slot']->id,
        'status' => 'pending',
        'attendee_name' => $patient->name,
        'attendee_age' => $patient->age,
        'attendee_country' => $patient->country,
        'payment_method' => 'clinic',
    ]);
    $fixture['slot']->update(['is_available' => false]);

    $this->actingAs($patient)->post(route('bookings.cancel', $booking))->assertRedirect();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($fixture['slot']->fresh()->is_available)->toBeTrue();
});
