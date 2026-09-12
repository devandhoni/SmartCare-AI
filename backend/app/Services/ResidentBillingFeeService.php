<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\ResidentAdmission;

class ResidentBillingFeeService
{
    public const REASON_NO_COMPLETED_ADMISSION =
        'NO_COMPLETED_ADMISSION';

    public const REASON_NO_ADMISSION_CONSENT =
        'NO_ADMISSION_CONSENT';

    public const REASON_CONSENT_NOT_COMPLETED =
        'CONSENT_NOT_COMPLETED';

    public const REASON_PAYMENT_TERMS_NOT_ACKNOWLEDGED =
        'PAYMENT_TERMS_NOT_ACKNOWLEDGED';

    public const REASON_AGREEMENT_NOT_ACKNOWLEDGED =
        'AGREEMENT_NOT_ACKNOWLEDGED';

    public const REASON_BILLING_FEE_NOT_CONFIGURED =
        'BILLING_FEE_NOT_CONFIGURED';

    public function resolveForResident(int $residentId): array
    {
        $resident = Resident::findOrFail($residentId);

        $admission = ResidentAdmission::with('consent')
            ->where('resident_id', $resident->id)
            ->where('status', 'COMPLETED')
            ->orderByDesc('admitted_at')
            ->orderByDesc('id')
            ->first();

        if (!$admission) {
            return $this->notConfigured(
                $resident->id,
                self::REASON_NO_COMPLETED_ADMISSION
            );
        }

        $consent = $admission->consent;

        if (!$consent) {
            return $this->notConfigured(
                $resident->id,
                self::REASON_NO_ADMISSION_CONSENT,
                $admission
            );
        }

        if ($consent->status !== 'COMPLETED') {
            return $this->notConfigured(
                $resident->id,
                self::REASON_CONSENT_NOT_COMPLETED,
                $admission,
                $consent
            );
        }

        if (!(bool) $consent->payment_fee_terms_acknowledged) {
            return $this->notConfigured(
                $resident->id,
                self::REASON_PAYMENT_TERMS_NOT_ACKNOWLEDGED,
                $admission,
                $consent
            );
        }

        if ($consent->agreement_acknowledged_at === null) {
            return $this->notConfigured(
                $resident->id,
                self::REASON_AGREEMENT_NOT_ACKNOWLEDGED,
                $admission,
                $consent
            );
        }

        if (
            $consent->monthly_fee === null
            || (float) $consent->monthly_fee <= 0
        ) {
            return $this->notConfigured(
                $resident->id,
                self::REASON_BILLING_FEE_NOT_CONFIGURED,
                $admission,
                $consent
            );
        }

        return [
            'configured' => true,
            'resident_id' => $resident->id,
            'monthly_fee' => $consent->monthly_fee,
            'resident_admission_id' => $admission->id,
            'admission_consent_id' => $consent->id,
            'agreement_title' => $consent->agreement_title,
            'agreement_version' => $consent->agreement_version,
            'agreement_acknowledged_at' =>
                $consent->agreement_acknowledged_at,
            'reason' => null,
        ];
    }

    private function notConfigured(
        int $residentId,
        string $reason,
        ?ResidentAdmission $admission = null,
        $consent = null
    ): array {
        return [
            'configured' => false,
            'resident_id' => $residentId,
            'monthly_fee' => null,
            'resident_admission_id' => $admission?->id,
            'admission_consent_id' => $consent?->id,
            'agreement_title' => $consent?->agreement_title,
            'agreement_version' => $consent?->agreement_version,
            'agreement_acknowledged_at' =>
                $consent?->agreement_acknowledged_at,
            'reason' => $reason,
        ];
    }
}