<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'resident_admission_consents',
            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Agreement Version
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'agreement_version',
                    50
                )->nullable();

                $table->string(
                    'agreement_title',
                    255
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Fees
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'monthly_fee',
                    10,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Actual Agreement Sections
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'medical_care_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'payment_fee_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'resident_conduct_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'belongings_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'termination_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'emergency_liability_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'risk_liability_terms_acknowledged'
                )->default(false);

                $table->boolean(
                    'death_event_terms_acknowledged'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Agreement Acknowledgement
                |--------------------------------------------------------------------------
                */

                $table->dateTime(
                    'agreement_acknowledged_at'
                )->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'resident_admission_consents',
            function (Blueprint $table) {
                $table->dropColumn([
                    'agreement_version',
                    'agreement_title',
                    'monthly_fee',

                    'medical_care_terms_acknowledged',
                    'payment_fee_terms_acknowledged',
                    'resident_conduct_terms_acknowledged',
                    'belongings_terms_acknowledged',
                    'termination_terms_acknowledged',
                    'emergency_liability_terms_acknowledged',
                    'risk_liability_terms_acknowledged',
                    'death_event_terms_acknowledged',

                    'agreement_acknowledged_at',
                ]);
            }
        );
    }
};