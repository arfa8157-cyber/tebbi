<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DoctorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_if($user->is_suspended, 403);
        $data = [];
        if ($user->role === 'admin') {
            $data = [
                'patientsCount' => User::where('role', 'patient')->count(),
                'doctorsCount' => DoctorProfile::where('approval_status', 'approved')->count(),
                'pendingDoctors' => DoctorProfile::with('user')->where('approval_status', 'pending')->latest()->get(),
                'bookingsCount' => Booking::count(),
                'activeUsers' => DB::table('sessions')->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->timestamp)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'reviews' => Review::with(['patient', 'doctorProfile.user'])->latest()->limit(30)->get(),
                'auditLogs' => DB::table('admin_audit_logs')->latest()->limit(20)->get(),
            ];
        } elseif ($user->role === 'doctor') {
            if ($user->doctorProfile?->approval_status !== 'approved') {
                return view('dashboard', ['user' => $user, 'data' => []]);
            }
            $profile = $user->doctorProfile()->with('clinics')->first();
            $bookings = Booking::with(['patient', 'clinic', 'slot'])->whereIn('clinic_id', $profile->clinics->pluck('id'))->latest()->get();
            $completedBookings = $bookings->where('status', 'completed');
            $completedCount = $completedBookings->count();
            $completedValue = $completedBookings->sum(fn (Booking $booking): int => $booking->clinic->consultation_fee);
            $data = compact('profile', 'bookings', 'completedCount', 'completedValue');
        } else {
            $bookings = $user->bookings()->with(['clinic.doctorProfile.user', 'slot', 'review'])->latest()->get();
            $doctorCounts = $bookings->groupBy(fn (Booking $booking): int => $booking->clinic->doctor_profile_id)
                ->map(fn ($doctorBookings): array => [
                    'upcoming' => $doctorBookings->filter(fn (Booking $booking): bool => $booking->status === 'confirmed' && $booking->slot->starts_at->isFuture())->count(),
                    'completed' => $doctorBookings->where('status', 'completed')->count(),
                ]);
            $data = compact('bookings', 'doctorCounts');
        }

        return view('dashboard', ['user' => $user, 'data' => $data]);
    }
}
