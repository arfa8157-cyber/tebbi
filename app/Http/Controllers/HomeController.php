<?php

namespace App\Http\Controllers;

use App\Models\DoctorProfile;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $topDoctors = DoctorProfile::query()->where('approval_status', 'approved')
            ->with(['user', 'clinics'])
            ->withAvg(['reviews' => fn ($reviews) => $reviews->where('is_visible', true)], 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->latest()
            ->limit(3)
            ->get();

        return view('welcome', compact('topDoctors'));
    }
}
