<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'resident_admission_consents',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Admission / Resident
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'resident_admission_id'
                );

                $table->bigInteger(
                    'resident_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Person Providing Consent
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'consent_given_by',
                    255
                );

                $table->string(
                    'relationship',
                    100
                )->nullable();

                $table->string(
                    'contact_number',
                    50
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Consent Statements
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'admission_consent'
                )->default(false);

                $table->boolean(
                    'care_consent'
                )->default(false);

                $table->boolean(
                    'medication_consent'
                )->default(false);

                $table->boolean(
                    'emergency_treatment_consent'
                )->default(false);

                $table->boolean(
                    'information_sharing_consent'
                )->default(false);

                $table->boolean(
                    'family_notification_consent'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Agreement
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'terms_acknowledged'
                )->default(false);

                $table->text(
                    'consent_notes'
                )->nullable();

                $table->dateTime(
                    'consented_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Staff Witness
                |--------------------------------------------------------------------------
                */

                $table->bigInteger(
                    'witnessed_by'
                )->nullable();

                $table->string(
                    'status',
                    50
                )->default('PENDING');

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'resident_admission_id'
                )
                    ->references('id')
                    ->on('resident_admissions')
                    ->cascadeOnDelete();

                $table->foreign(
                    'resident_id'
                )
                    ->references('id')
                    ->on('residents')
                    ->cascadeOnDelete();

                $table->foreign(
                    'witnessed_by'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'resident_admission_id',
                        'status',
                    ],
                    'rac_admission_status_idx'
                );

                $table->index(
                    [
                        'resident_id',
                        'consented_at',
                    ],
                    'rac_resident_date_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_admission_consents'
        );
    }
};