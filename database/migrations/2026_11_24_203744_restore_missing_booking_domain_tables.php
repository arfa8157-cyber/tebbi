<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctor_profiles')) {
            Schema::create('doctor_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('specialty');
                $table->text('bio');
                $table->string('approval_status')->default('pending')->index();
                $table->string('document_path')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('clinics')) {
            Schema::create('clinics', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('address');
                $table->unsignedInteger('consultation_fee');
                $table->string('timezone')->default('Africa/Cairo');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('appointment_slots')) {
            Schema::create('appointment_slots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->boolean('is_available')->default(true);
                $table->timestamps();
                $table->index(['clinic_id', 'starts_at', 'is_available']);
            });
        }

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table): void {
                $table->id();
                $table->string('reference', 20)->unique();
                $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_slot_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('pending')->index();
                $table->boolean('for_someone_else')->default(false);
                $table->string('attendee_name');
                $table->unsignedTinyInteger('attendee_age');
                $table->string('attendee_country');
                $table->text('reason')->nullable();
                $table->string('payment_method')->default('clinic');
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'status']);
            });
        }

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('comment');
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->string('action');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('appointment_slots');
        Schema::dropIfExists('clinics');
        Schema::dropIfExists('doctor_profiles');
    }
};
