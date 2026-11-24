<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $fillable = ['doctor_profile_id', 'name', 'address', 'consultation_fee', 'timezone'];

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class);
    }
}
