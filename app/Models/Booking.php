<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = ['reference', 'patient_id', 'clinic_id', 'appointment_slot_id', 'status', 'for_someone_else', 'attendee_name', 'attendee_age', 'attendee_country', 'reason', 'payment_method', 'decided_at'];

    protected function casts(): array
    {
        return ['for_someone_else' => 'boolean', 'decided_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'appointment_slot_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
