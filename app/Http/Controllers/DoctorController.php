<?php

namespace App\Http\Controllers;

use App\Models\DoctorProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        $query = DoctorProfile::query()->where('approval_status', 'approved')->with(['user', 'clinics'])
            ->withAvg(['reviews' => fn ($reviews) => $reviews->where('is_visible', true)], 'rating')
            ->withCount(['reviews' => fn ($reviews) => $reviews->where('is_visible', true)]);
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('specialty', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('clinics', fn ($clinics) => $clinics->where('address', 'like', '%'.$search.'%'));
            });
        }
        $doctors = $query->latest()->paginate(9)->withQueryString();

        return view('doctors', compact('doctors', 'search'));
    }

    public function show(DoctorProfile $doctor): View
    {
        abort_unless($doctor->approval_status === 'approved', 404);
        $doctor->load(['user', 'reviews' => fn ($query) => $query->where('is_visible', true)->with('patient')->latest(), 'clinics.slots' => fn ($query) => $query->where('is_available', true)->where('starts_at', '>', now())->orderBy('starts_at')]);

        return view('doctor', compact('doctor'));
    }
}
