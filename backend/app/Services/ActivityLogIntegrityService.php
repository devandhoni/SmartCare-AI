<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Stage-one, opt-in integrity baseline. Not connected to audit creation yet. */
class ActivityLogIntegrityService
{
    private const GENESIS = 'GENESIS';

    public function append(ActivityLog $log): void
    {
        DB::transaction(function () use ($log): void {
            if (! $log->exists || $log->getKey() === null) {
                throw new LogicException('The activity log must already be saved.');
            }

            if (DB::table('activity_log_integrity')->where('activity_log_id', $log->getKey())->exists()) {
                throw new LogicException('This activity log already has an integrity entry.');
            }

            $last = DB::table('activity_log_integrity')->orderByDesc('id')->first();
            $previous = $last?->chain_hash;
            $payload = $this->payloadHash($log->fresh());
            $chain = $this->chainHash($previous, (int) $log->getKey(), $payload);

            DB::table('activity_log_integrity')->insert([
                'activity_log_id' => $log->getKey(),
                'previous_hash' => $previous,
                'payload_hash' => $payload,
                'chain_hash' => $chain,
                'created_at' => now(),
            ]);
        });
    }

    /** @return array{valid: bool, checked: int, failed_entry_id: int|null, reason: string|null} */
    public function verify(): array
    {
        $previous = null;
        $checked = 0;

        foreach (DB::table('activity_log_integrity')->orderBy('id')->cursor() as $entry) {
            $fail = function (string $reason) use ($entry, $checked): array {
                return ['valid' => false, 'checked' => $checked, 'failed_entry_id' => (int) $entry->id, 'reason' => $reason];
            };

            if (! hash_equals($previous ?? self::GENESIS, $entry->previous_hash ?? self::GENESIS)) {
                return $fail('Broken previous-hash link.');
            }

            $log = ActivityLog::find($entry->activity_log_id);
            if ($log === null) {
                return $fail('Protected activity log is missing.');
            }

            $payload = $this->payloadHash($log);
            if (! hash_equals($payload, $entry->payload_hash)) {
                return $fail('Protected activity log content has changed.');
            }

            $expected = $this->chainHash($previous, (int) $entry->activity_log_id, $payload);
            if (! hash_equals($expected, $entry->chain_hash)) {
                return $fail('Chain hash does not match.');
            }

            $previous = $entry->chain_hash;
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'failed_entry_id' => null, 'reason' => null];
    }

    private function payloadHash(ActivityLog $log): string
    {
        // Explicit field order and string conversion make the representation deterministic.
        return hash('sha256', json_encode([
            'id' => (int) $log->getKey(),
            'user_id' => $log->user_id === null ? null : (int) $log->user_id,
            'resident_id' => $log->resident_id === null ? null : (int) $log->resident_id,
            'module' => $log->module,
            'action' => $log->action,
            'description' => $log->description,
            'created_on' => $log->getRawOriginal('created_on'),
            'updated_on' => $log->getRawOriginal('updated_on'),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function chainHash(?string $previous, int $logId, string $payload): string
    {
        return hash('sha256', json_encode([
            'previous' => $previous ?? self::GENESIS,
            'activity_log_id' => $logId,
            'payload_hash' => $payload,
        ], JSON_THROW_ON_ERROR));
    }
}
