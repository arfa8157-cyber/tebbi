<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DoctorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function document(DoctorProfile $doctor): StreamedResponse
    {
        abort_unless($doctor->document_path, 404);

        return Storage::download($doctor->document_path);
    }

    public function review(Request $request, DoctorProfile $doctor): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);
        abort_unless($doctor->approval_status === 'pending', 409);

        DB::transaction(function () use ($request, $doctor, $data): void {
            $doctor->update(['approval_status' => $data['decision'], 'admin_note' => $data['admin_note'] ?? null]);
            if ($data['decision'] === 'rejected') {
                $doctor->user()->update(['role' => 'patient']);
            }
            DB::table('admin_audit_logs')->insert([
                'admin_id' => $request->user()->id, 'action' => 'doctor_application_'.$data['decision'],
                'subject_type' => DoctorProfile::class, 'subject_id' => $doctor->id,
                'details' => json_encode(['note' => $data['admin_note'] ?? null]), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return back()->with('success', $data['decision'] === 'approved' ? 'تم اعتماد ملف الدكتور.' : 'تم رفض الطلب مع إبقاء الحساب كمستخدم عادي.');
    }

    public function accounts(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $accounts = User::query()->where('role', '!=', 'admin')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->latest()->paginate(20)->withQueryString();

        return view('accounts', compact('accounts', 'search'));
    }

    public function bookings(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $bookings = Booking::query()->with(['patient', 'clinic.doctorProfile.user', 'slot'])
            ->when(in_array($status, ['pending', 'confirmed', 'declined', 'completed', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhereHas('patient', fn ($patients) => $patients->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('clinic.doctorProfile.user', fn ($doctors) => $doctors->where('name', 'like', '%'.$search.'%'))))
            ->latest()->paginate(25)->withQueryString();

        return view('admin-bookings', compact('bookings', 'search', 'status'));
    }

    public function toggleSuspension(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role === 'admin', 403);
        $user->update(['is_suspended' => ! $user->is_suspended]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('admin_audit_logs')->insert([
            'admin_id' => $request->user()->id, 'action' => $user->is_suspended ? 'account_suspended' : 'account_reactivated',
            'subject_type' => $user::class, 'subject_id' => $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', $user->is_suspended ? 'تم إيقاف الحساب.' : 'تمت إعادة تفعيل الحساب.');
    }

    public function moderateReview(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate(['is_visible' => ['required', 'boolean']]);
        $review->update(['is_visible' => $data['is_visible']]);
        DB::table('admin_audit_logs')->insert([
            'admin_id' => $request->user()->id,
            'action' => $review->is_visible ? 'review_restored' : 'review_hidden',
            'subject_type' => Review::class,
            'subject_id' => $review->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', $review->is_visible ? 'تم إظهار التقييم.' : 'تم إخفاء التقييم.');
    }
}
