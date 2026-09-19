<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Services\ActivityLogCheckpointService;
use App\Services\ActivityLogIntegrityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class F13ActivityLogCheckpointTest extends TestCase
{
    private string $checkpointDirectory;
    private ActivityLogCheckpointService $checkpoints;
    private ActivityLogIntegrityService $integrity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('resident_id')->nullable();
            $table->string('module');
            $table->string('action');
            $table->text('description');
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        Schema::create('activity_log_integrity', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('activity_log_id')->unique();
            $table->char('previous_hash', 64)->nullable();
            $table->char('payload_hash', 64);
            $table->char('chain_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        $this->checkpointDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'smartcare-f13-checkpoint-'.bin2hex(random_bytes(12));
        if (! mkdir($this->checkpointDirectory, 0700)) {
            throw new RuntimeException('Unable to create isolated checkpoint test directory.');
        }
        $this->checkpoints = new ActivityLogCheckpointService($this->checkpointDirectory, str_repeat('test-only-secret-', 4));
        $this->integrity = new ActivityLogIntegrityService();
    }

    protected function tearDown(): void
    {
        if (isset($this->checkpointDirectory) && is_dir($this->checkpointDirectory)) {
            foreach (glob($this->checkpointDirectory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->checkpointDirectory);
        }
        parent::tearDown();
    }

    private function addProtectedLog(int $id): void
    {
        // Bypass model creation hooks deliberately; invoke append exactly once.
        DB::table('activity_logs')->insert([
            'id' => $id,
            'user_id' => null,
            'resident_id' => null,
            'module' => 'F13 Test',
            'action' => 'CREATE',
            'description' => 'PRIVATE-TEST-CONTENT-'.$id,
            'created_on' => '2026-09-19 12:00:00',
            'updated_on' => null,
        ]);
        $this->integrity->append(ActivityLog::findOrFail($id));
    }

    public function test_checkpoint_requires_existing_valid_chain_and_rejects_empty_baseline(): void
    {
        $this->assertFalse($this->checkpoints->verify()['valid']);
        $this->assertSame('No checkpoint files are available.', $this->checkpoints->verify()['reason']);

        try {
            $this->checkpoints->create($this->integrity);
            $this->fail('An empty chain must not produce a checkpoint.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot checkpoint an empty integrity chain.', $exception->getMessage());
        }

        $this->addProtectedLog(1);
        DB::table('activity_logs')->where('id', 1)->update(['description' => 'TAMPERED']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot checkpoint a chain that fails verification.');
        $this->checkpoints->create($this->integrity);
    }

    public function test_signed_checkpoint_verifies_and_allows_later_valid_entries(): void
    {
        $this->addProtectedLog(1);
        $first = $this->checkpoints->create($this->integrity);
        $this->assertFileExists($first);
        $this->assertSame(1, $this->checkpoints->verify()['checked_checkpoints']);

        $this->addProtectedLog(2);
        $this->assertTrue($this->integrity->verify()['valid']);
        $this->assertTrue($this->checkpoints->verify()['valid']);

        $second = $this->checkpoints->create($this->integrity);
        $this->assertNotSame($first, $second);
        $this->assertSame(2, $this->checkpoints->verify()['checked_checkpoints']);
        $this->assertStringNotContainsString('PRIVATE-TEST-CONTENT', file_get_contents($first));
    }

    public function test_checkpoint_detects_deletion_of_final_protected_entry(): void
    {
        $this->addProtectedLog(1);
        $this->addProtectedLog(2);
        $this->checkpoints->create($this->integrity);

        DB::table('activity_log_integrity')->where('activity_log_id', 2)->delete();
        $this->assertTrue($this->integrity->verify()['valid'], 'Remaining chain alone can appear valid.');
        $result = $this->checkpoints->verify();
        $this->assertFalse($result['valid']);
        $this->assertSame('Database chain no longer matches a retained checkpoint.', $result['reason']);
    }

    public function test_checkpoint_detects_modified_file_and_missing_all_files(): void
    {
        $this->addProtectedLog(1);
        $file = $this->checkpoints->create($this->integrity);
        $document = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $document['snapshot']['count'] = 999;
        file_put_contents($file, json_encode($document, JSON_THROW_ON_ERROR));

        $this->assertSame('Checkpoint signature does not match.', $this->checkpoints->verify()['reason']);
        unlink($file);
        $this->assertFalse($this->checkpoints->verify()['valid']);
        $this->assertSame('No checkpoint files are available.', $this->checkpoints->verify()['reason']);
    }

    public function test_checkpoint_requires_a_sufficiently_long_signing_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A checkpoint signing key of at least 32 bytes is required.');
        new ActivityLogCheckpointService($this->checkpointDirectory, 'short');
    }
}
