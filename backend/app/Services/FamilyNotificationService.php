<?php

namespace App\Services;

use App\Contracts\WhatsAppProvider;
use App\Models\FamilyMessageLog;
use App\Models\MedicationAdministrationRecord;
use App\Models\Resident;
use App\Models\ResidentContact;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FamilyNotificationService
{
    public function __construct(
        private readonly WhatsAppProvider $whatsAppProvider
    ) {
    }

    /**
     * Create and submit the medication + meal success message for every
     * opted-in WhatsApp contact.
     *
     * Clinical actions are already committed before this service is called.
     * Provider failures are captured in family_message_logs and are never
     * re-thrown into the medication workflow.
     */
    public function medicationMealCompleted(
        MedicationAdministrationRecord $record
    ): array {
        $record->loadMissing([
            'residentMedication.medication',
        ]);

        if (!$this->isMedicationMealEligible($record)) {
            return [
                'eligible' => false,
                'created' => 0,
                'existing' => 0,
                'failed' => 0,
                'logs' => [],
            ];
        }

        $resident = Resident::find($record->resident_id);

        if (!$resident) {
            return [
                'eligible' => false,
                'created' => 0,
                'existing' => 0,
                'failed' => 0,
                'logs' => [],
            ];
        }

        $contacts = ResidentContact::query()
            ->where('resident_id', $record->resident_id)
            ->where('whatsapp_enabled', true)
            ->where('medication_notifications_enabled', true)
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '<>', '')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $message = $this->medicationMealMessage($resident, $record);

        $created = 0;
        $existing = 0;
        $failed = 0;
        $logs = [];

        foreach ($contacts as $contact) {
            $result = $this->createAndSubmitLog(
                $resident,
                $contact,
                $record,
                $message
            );

            $logs[] = $result['log'];

            if ($result['created']) {
                $created++;
            } else {
                $existing++;
            }

            if ($result['log']->status === 'FAILED') {
                $failed++;
            }
        }

        return [
            'eligible' => true,
            'created' => $created,
            'existing' => $existing,
            'failed' => $failed,
            'logs' => $logs,
        ];
    }

    /**
     * Retry one previously FAILED family message.
     *
     * The same family_message_logs row is reused; retry never creates a new
     * log entry. SENT, PENDING, or SKIPPED records are deliberately rejected.
     */
    public function retryFailedLog(int $logId): FamilyMessageLog
    {
        $log = FamilyMessageLog::query()
            ->findOrFail($logId);

        if ($log->status !== 'FAILED') {
            throw new RuntimeException(
                'Only FAILED family messages can be retried.'
            );
        }

        return $this->submitLog($log);
    }

    private function isMedicationMealEligible(
        MedicationAdministrationRecord $record
    ): bool {
        return strtoupper((string) $record->status) === 'COMPLETED'
            && $record->meal_type !== null
            && (bool) $record->meal_confirmed;
    }

    private function medicationMealMessage(
        Resident $resident,
        MedicationAdministrationRecord $record
    ): string {
        $slot = strtoupper((string) $record->time_slot);
        $meal = strtolower((string) $record->meal_type);

        return $resident->full_name
            . ' has taken the '
            . $slot
            . ' medication and '
            . $meal
            . '.';
    }

    private function createAndSubmitLog(
        Resident $resident,
        ResidentContact $contact,
        MedicationAdministrationRecord $record,
        string $message
    ): array {
        $log = null;
        $created = false;

        DB::transaction(function () use (
            $resident,
            $contact,
            $record,
            $message,
            &$log,
            &$created
        ) {
            $log = FamilyMessageLog::query()
                ->where('source_type', 'MedicationAdministrationRecord')
                ->where('source_id', $record->id)
                ->where('resident_contact_id', $contact->id)
                ->where('message_type', 'MEDICATION_MEAL_COMPLETED')
                ->lockForUpdate()
                ->first();

            if ($log) {
                return;
            }

            $log = FamilyMessageLog::create([
                'resident_id' => $resident->id,
                'resident_contact_id' => $contact->id,
                'channel' => 'WHATSAPP',
                'message_type' => 'MEDICATION_MEAL_COMPLETED',
                'message' => $message,
                'source_type' => 'MedicationAdministrationRecord',
                'source_id' => $record->id,
                'recipient_name' => $contact->full_name,
                'recipient_number' => trim((string) $contact->whatsapp_number),
                'status' => 'PENDING',
                'provider' => $this->whatsAppProvider->name(),
                'attempt_count' => 0,
                'created_by' => auth()->id(),
            ]);

            $created = true;
        });

        /*
         * Existing logs are not automatically re-submitted here. A failed
         * delivery must go through retryFailedLog(), which makes retries
         * explicit and auditable.
         */
        if (!$created) {
            return [
                'created' => false,
                'log' => $log,
            ];
        }

        $log = $this->submitLog($log);

        return [
            'created' => true,
            'log' => $log,
        ];
    }

    /**
     * Submit one existing log through the configured provider.
     *
     * NULL remains a safe dry-run provider. It leaves the record PENDING and
     * does not increment attempt_count because no delivery attempt occurred.
     *
     * Real/simulated provider exceptions are converted into FAILED state.
     */
    private function submitLog(FamilyMessageLog $log): FamilyMessageLog
    {
        if ($this->whatsAppProvider->name() === 'NULL') {
            if ($log->provider !== 'NULL') {
                $log->update([
                    'provider' => 'NULL',
                ]);
            }

            return $log->fresh();
        }

        try {
            $providerMessageId = $this->whatsAppProvider->send(
                $log->recipient_number,
                $log->message
            );

            $log->update([
                'status' => 'SENT',
                'provider' => $this->whatsAppProvider->name(),
                'provider_message_id' => $providerMessageId,
                'attempt_count' => (int) $log->attempt_count + 1,
                'sent_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'FAILED',
                'provider' => $this->whatsAppProvider->name(),
                'provider_message_id' => null,
                'attempt_count' => (int) $log->attempt_count + 1,
                'sent_at' => null,
                'failed_at' => now(),
                'failure_reason' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ]);
        }

        return $log->fresh();
    }
}
