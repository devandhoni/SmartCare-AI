<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentAdmissionConsent extends Model
{
    protected $fillable = [
        'resident_admission_id',
        'resident_id',
        'consent_given_by',
        'relationship',
        'contact_number',
        'admission_consent',
        'care_consent',
        'medication_consent',
        'emergency_treatment_consent',
        'information_sharing_consent',
        'family_notification_consent',
        'terms_acknowledged',
        'consent_notes',
        'consented_at',
        'witnessed_by',
        'status',
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
    ];

    protected $casts = [
        'admission_consent' => 'boolean',
        'care_consent' => 'boolean',
        'medication_consent' => 'boolean',
        'emergency_treatment_consent' => 'boolean',
        'information_sharing_consent' => 'boolean',
        'family_notification_consent' => 'boolean',
        'terms_acknowledged' => 'boolean',
        'consented_at' => 'datetime',
        'medical_care_terms_acknowledged' => 'boolean',
        'payment_fee_terms_acknowledged' => 'boolean',
        'resident_conduct_terms_acknowledged' => 'boolean',
        'belongings_terms_acknowledged' => 'boolean',
        'termination_terms_acknowledged' => 'boolean',
        'emergency_liability_terms_acknowledged' => 'boolean',
        'risk_liability_terms_acknowledged' => 'boolean',
        'death_event_terms_acknowledged' => 'boolean',
        'agreement_acknowledged_at' => 'datetime',
        'monthly_fee' => 'decimal:2',
    ];

    public function admission()
    {
        return $this->belongsTo(
            ResidentAdmission::class,
            'resident_admission_id'
        );
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function witness()
    {
        return $this->belongsTo(
            User::class,
            'witnessed_by'
        );
    }
}