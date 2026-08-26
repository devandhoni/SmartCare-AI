<?php

namespace App\Http\Controllers;

use App\Models\ResidentAdmission;
use App\Models\ResidentAdmissionConsent;
use Illuminate\Http\Request;

class ResidentAdmissionConsentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Save / Update Consent
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        $admissionId
    ) {
        $admission =
            ResidentAdmission::findOrFail(
                $admissionId
            );

        $validated =
            $request->validate([
                'consent_given_by' =>
                    'required|string|max:255',

                'relationship' =>
                    'nullable|string|max:100',

                'contact_number' =>
                    'nullable|string|max:50',

                'admission_consent' =>
                    'required|boolean',

                'care_consent' =>
                    'required|boolean',

                'medication_consent' =>
                    'required|boolean',

                'emergency_treatment_consent' =>
                    'required|boolean',

                'information_sharing_consent' =>
                    'required|boolean',

                'family_notification_consent' =>
                    'required|boolean',

                'terms_acknowledged' =>
                    'required|boolean',

                'consent_notes' =>
                    'nullable|string',
            ]);

        $allRequiredAccepted =
            $validated['admission_consent']
            &&
            $validated['care_consent']
            &&
            $validated['medication_consent']
            &&
            $validated[
                'emergency_treatment_consent'
            ]
            &&
            $validated[
                'terms_acknowledged'
            ];

        $consent =
            ResidentAdmissionConsent::updateOrCreate(
                [
                    'resident_admission_id' =>
                        $admission->id,
                ],
                [
                    'resident_id' =>
                        $admission->resident_id,

                    'consent_given_by' =>
                        $validated[
                            'consent_given_by'
                        ],

                    'relationship' =>
                        $validated['relationship']
                        ?? null,

                    'contact_number' =>
                        $validated['contact_number']
                        ?? null,

                    'admission_consent' =>
                        $validated[
                            'admission_consent'
                        ],

                    'care_consent' =>
                        $validated[
                            'care_consent'
                        ],

                    'medication_consent' =>
                        $validated[
                            'medication_consent'
                        ],

                    'emergency_treatment_consent' =>
                        $validated[
                            'emergency_treatment_consent'
                        ],

                    'information_sharing_consent' =>
                        $validated[
                            'information_sharing_consent'
                        ],

                    'family_notification_consent' =>
                        $validated[
                            'family_notification_consent'
                        ],

                    'terms_acknowledged' =>
                        $validated[
                            'terms_acknowledged'
                        ],

                    'consent_notes' =>
                        $validated['consent_notes']
                        ?? null,

                    'consented_at' =>
                        $allRequiredAccepted
                            ? now()
                            : null,

                    'witnessed_by' =>
                        auth()->id(),

                    'status' =>
                        $allRequiredAccepted
                            ? 'COMPLETED'
                            : 'PENDING',
                ]
            );

        $consent->load(
            'witness:id,full_name'
        );

        return response()->json([
            'message' =>
                $allRequiredAccepted
                    ? 'Admission consent completed successfully.'
                    : 'Admission consent saved as pending.',

            'consent' =>
                $consent,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | View Admission Consent
    |--------------------------------------------------------------------------
    */

    public function show($admissionId)
    {
        $admission =
            ResidentAdmission::findOrFail(
                $admissionId
            );

        $consent =
            ResidentAdmissionConsent::with(
                'witness:id,full_name'
            )
            ->where(
                'resident_admission_id',
                $admission->id
            )
            ->first();

        return response()->json([
            'consent' =>
                $consent,
        ]);
    }
}