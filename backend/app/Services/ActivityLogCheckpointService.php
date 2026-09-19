<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Explicit, opt-in local checkpoint support. No automatic writes or database mutations.
 * The signing key must be supplied separately from the checkpoint directory.
 */
class ActivityLogCheckpointService
{
    public function __construct(private readonly string $directory, private readonly string $signingKey)
    {
        if (strlen($this->signingKey) < 32) {
            throw new RuntimeException('A checkpoint signing key of at least 32 bytes is required.');
        }
    }

    /** @return array{entry_id: int, activity_log_id: int, count: int, chain_hash: string} */
    public function snapshot(): array
    {
        $last = DB::table('activity_log_integrity')->orderByDesc('id')->first();
        if ($last === null) {
            throw new RuntimeException('Cannot checkpoint an empty integrity chain.');
        }

        return [
            'entry_id' => (int) $last->id,
            'activity_log_id' => (int) $last->activity_log_id,
            'count' => (int) DB::table('activity_log_integrity')->count(),
            'chain_hash' => (string) $last->chain_hash,
        ];
    }

    /**
     * Write an immutable-named checkpoint only after verifying the current chain.
     * Call explicitly from a controlled maintenance workflow, never on every API GET.
     */
    public function create(ActivityLogIntegrityService $integrity): string
    {
        $result = $integrity->verify();
        if (! $result['valid']) {
            throw new RuntimeException('Cannot checkpoint a chain that fails verification.');
        }

        $snapshot = $this->snapshot();
        if ($snapshot['count'] !== $result['checked']) {
            throw new RuntimeException('Integrity chain changed during checkpoint preparation.');
        }

        $payload = ['version' => 1, 'snapshot' => $snapshot];
        $signature = hash_hmac('sha256', $this->encode($payload), $this->signingKey);
        $document = $payload + ['signature' => $signature];

        if (! is_dir($this->directory) || ! is_writable($this->directory)) {
            throw new RuntimeException('Checkpoint directory must already exist and be writable.');
        }

        $path = rtrim($this->directory, '\\/').DIRECTORY_SEPARATOR
            .'audit-checkpoint-'.gmdate('Ymd\THis\Z').'-'.bin2hex(random_bytes(8)).'.json';
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            throw new RuntimeException('Unable to create a new checkpoint file.');
        }

        try {
            $bytes = $this->encode($document)."\n";
            if (fwrite($handle, $bytes) !== strlen($bytes) || ! fflush($handle)) {
                throw new RuntimeException('Unable to write the complete checkpoint.');
            }
        } catch (\Throwable $exception) {
            fclose($handle);
            @unlink($path);
            throw $exception;
        }
        fclose($handle);

        return $path;
    }

    /**
     * Verify every retained checkpoint, not just the newest one.
     * Missing *all* checkpoint files is an error, not a successful empty baseline.
     *
     * @return array{valid: bool, checked_checkpoints: int, reason: string|null}
     */
    public function verify(): array
    {
        $failure = static fn (int $checked, string $reason): array => [
            'valid' => false, 'checked_checkpoints' => $checked, 'reason' => $reason,
        ];

        $files = glob(rtrim($this->directory, '\\/').DIRECTORY_SEPARATOR.'audit-checkpoint-*.json');
        if ($files === false || $files === []) {
            return $failure(0, 'No checkpoint files are available.');
        }

        sort($files, SORT_STRING);
        $checked = 0;
        foreach ($files as $file) {
            $contents = @file_get_contents($file);
            $document = $contents === false ? null : json_decode($contents, true);
            if (! is_array($document) || array_keys($document) !== ['version', 'snapshot', 'signature']
                || $document['version'] !== 1 || ! is_array($document['snapshot'])
                || array_keys($document['snapshot']) !== ['entry_id', 'activity_log_id', 'count', 'chain_hash']
                || ! is_int($document['snapshot']['entry_id']) || ! is_int($document['snapshot']['activity_log_id'])
                || ! is_int($document['snapshot']['count']) || ! is_string($document['snapshot']['chain_hash'])
                || ! preg_match('/^[a-f0-9]{64}$/D', $document['snapshot']['chain_hash'])
                || ! is_string($document['signature']) || ! preg_match('/^[a-f0-9]{64}$/D', $document['signature'])) {
                return $failure($checked, 'Checkpoint file is unreadable or malformed.');
            }

            $payload = ['version' => 1, 'snapshot' => $document['snapshot']];
            $expected = hash_hmac('sha256', $this->encode($payload), $this->signingKey);
            if (! hash_equals($expected, $document['signature'])) {
                return $failure($checked, 'Checkpoint signature does not match.');
            }

            $snapshot = $document['snapshot'];
            $entry = DB::table('activity_log_integrity')->where('id', $snapshot['entry_id'])->first();
            if ($entry === null || (int) $entry->activity_log_id !== $snapshot['activity_log_id']
                || ! hash_equals($snapshot['chain_hash'], (string) $entry->chain_hash)
                || DB::table('activity_log_integrity')->where('id', '<=', $snapshot['entry_id'])->count() !== $snapshot['count']) {
                return $failure($checked, 'Database chain no longer matches a retained checkpoint.');
            }
            $checked++;
        }

        return ['valid' => true, 'checked_checkpoints' => $checked, 'reason' => null];
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
