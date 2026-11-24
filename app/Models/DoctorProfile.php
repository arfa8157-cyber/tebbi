<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'specialty', 'bio', 'approval_status', 'document_path', 'admin_note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
