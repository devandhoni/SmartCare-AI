<?php

namespace App\Http\Controllers;

use App\Models\ResidentAdmission;
use App\Models\ResidentAdmissionConsent;
use Illuminate\Http\Request;

class ResidentAdmissionConsentController extends Controller
{
    public function store(Request $request, $admissionId)
    {
        $admission = ResidentAdmission::findOrFail($admissionId);

        $validated = $request->validate([
            'consent_given_by' => 'required|string|max:255',
            'relationship' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:50',

            'admission_consent' => 'required|boolean',
            'care_consent' => 'required|boolean',
            'medication_consent' => 'required|boolean',
            'emergency_treatment_consent' => 'required|boolean',
            'information_sharing_consent' => 'required|boolean',
            'family_notification_consent' => 'required|boolean',
            'terms_acknowledged' => 'required|boolean',
            'consent_notes' => 'nullable|string',

            'agreement_version' => 'nullable|string|max:50',
            'agreement_title' => 'nullable|string|max:255',
            'monthly_fee' => 'nullable|numeric|min:0|max:99999999.99',

            'medical_care_terms_acknowledged' => 'sometimes|boolean',
            'payment_fee_terms_acknowledged' => 'sometimes|boolean',
            'resident_conduct_terms_acknowledged' => 'sometimes|boolean',
            'belongings_terms_acknowledged' => 'sometimes|boolean',
            'termination_terms_acknowledged' => 'sometimes|boolean',
            'emergency_liability_terms_acknowledged' => 'sometimes|boolean',
            'risk_liability_terms_acknowledged' => 'sometimes|boolean',
            'death_event_terms_acknowledged' => 'sometimes|boolean',
        ]);

        $coreConsentAccepted =
            $validated['admission_consent']
            && $validated['care_consent']
            && $validated['medication_consent']
            && $validated['emergency_treatment_consent']
            && $validated['terms_acknowledged'];

        $enhancedAgreementUsed = !empty($validated['agreement_version']);

        $agreementSectionsAccepted =
            ($validated['medical_care_terms_acknowledged'] ?? false)
            && ($validated['payment_fee_terms_acknowledged'] ?? false)
            && ($validated['resident_conduct_terms_acknowledged'] ?? false)
            && ($validated['belongings_terms_acknowledged'] ?? false)
            && ($validated['termination_terms_acknowledged'] ?? false)
            && ($validated['emergency_liability_terms_acknowledged'] ?? false)
            && ($validated['risk_liability_terms_acknowledged'] ?? false)
            && ($validated['death_event_terms_acknowledged'] ?? false);

        $allRequiredAccepted =
            $coreConsentAccepted
            && (!$enhancedAgreementUsed || $agreementSectionsAccepted);

        $consent = ResidentAdmissionConsent::updateOrCreate(
            [
                'resident_admission_id' => $admission->id,
            ],
            [
                'resident_id' => $admission->resident_id,
                'consent_given_by' => $validated['consent_given_by'],
                'relationship' => $validated['relationship'] ?? null,
                'contact_number' => $validated['contact_number'] ?? null,

                'admission_consent' => $validated['admission_consent'],
                'care_consent' => $validated['care_consent'],
                'medication_consent' => $validated['medication_consent'],
                'emergency_treatment_consent' =>
                    $validated['emergency_treatment_consent'],
                'information_sharing_consent' =>
                    $validated['information_sharing_consent'],
                'family_notification_consent' =>
                    $validated['family_notification_consent'],
                'terms_acknowledged' => $validated['terms_acknowledged'],

                'consent_notes' => $validated['consent_notes'] ?? null,

                'agreement_version' => $validated['agreement_version'] ?? null,
                'agreement_title' => $validated['agreement_title'] ?? null,
                'monthly_fee' => $validated['monthly_fee'] ?? null,

                'medical_care_terms_acknowledged' =>
                    $validated['medical_care_terms_acknowledged'] ?? false,
                'payment_fee_terms_acknowledged' =>
                    $validated['payment_fee_terms_acknowledged'] ?? false,
                'resident_conduct_terms_acknowledged' =>
                    $validated['resident_conduct_terms_acknowledged'] ?? false,
                'belongings_terms_acknowledged' =>
                    $validated['belongings_terms_acknowledged'] ?? false,
                'termination_terms_acknowledged' =>
                    $validated['termination_terms_acknowledged'] ?? false,
                'emergency_liability_terms_acknowledged' =>
                    $validated['emergency_liability_terms_acknowledged'] ?? false,
                'risk_liability_terms_acknowledged' =>
                    $validated['risk_liability_terms_acknowledged'] ?? false,
                'death_event_terms_acknowledged' =>
                    $validated['death_event_terms_acknowledged'] ?? false,

                'agreement_acknowledged_at' =>
                    ($enhancedAgreementUsed && $agreementSectionsAccepted)
                        ? now()
                        : null,

                'consented_at' => $allRequiredAccepted ? now() : null,
                'witnessed_by' => auth()->id(),
                'status' => $allRequiredAccepted ? 'COMPLETED' : 'PENDING',
            ]
        );

        $consent->load('witness:id,full_name');

        return response()->json([
            'message' => $allRequiredAccepted
                ? 'Admission consent and agreement completed successfully.'
                : (
                    $enhancedAgreementUsed
                        ? 'Admission consent saved as pending. All required consent controls and all eight agreement sections must be acknowledged.'
                        : 'Admission consent saved as pending.'
                ),
            'consent' => $consent,
        ]);
    }

    public function show($admissionId)
    {
        $admission = ResidentAdmission::findOrFail($admissionId);

        $consent = ResidentAdmissionConsent::with(
                'witness:id,full_name'
            )
            ->where(
                'resident_admission_id',
                $admission->id
            )
            ->first();

        return response()->json([
            'consent' => $consent,
        ]);
    }
}