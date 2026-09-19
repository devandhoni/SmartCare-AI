<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Services\ActivityLogIntegrityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F13ActivityLogIntegrityServiceTest extends TestCase
{
    public function test_opt_in_chain_detects_changed_and_missing_protected_records(): void
    {
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

        // Use the actual migration to test its schema, not a hand-built approximation.
        $migration = require database_path('migrations/2026_09_19_000001_create_activity_log_integrity_table.php');
        $migration->up();

        $service = app(ActivityLogIntegrityService::class);
        DB::table('activity_logs')->insert(['module' => 'Old', 'action' => 'OLD', 'description' => 'Historical record']);
        $historicalId = (int) DB::table('activity_logs')->where('module', 'Old')->value('id');
        $first = ActivityLog::create(['module' => 'Staff', 'action' => 'CREATE', 'description' => 'First protected record']);
        $second = ActivityLog::create(['module' => 'Staff', 'action' => 'UPDATE', 'description' => 'Second protected record']);

        $this->assertSame(2, DB::table('activity_log_integrity')->count());
        $this->assertSame(['valid' => true, 'checked' => 2, 'failed_entry_id' => null, 'reason' => null], $service->verify());
        $this->assertFalse(DB::table('activity_log_integrity')->where('activity_log_id', $historicalId)->exists());

        DB::table('activity_logs')->where('id', $second->id)->update(['description' => 'Altered']);
        $this->assertSame('Protected activity log content has changed.', $service->verify()['reason']);

        DB::table('activity_logs')->where('id', $first->id)->delete();
        $this->assertSame('Protected activity log is missing.', $service->verify()['reason']);

        // If integrity storage is unavailable, a new audit insert must roll back.
        Schema::drop('activity_log_integrity');
        $before = DB::table('activity_logs')->count();
        try {
            ActivityLog::create(['module' => 'Staff', 'action' => 'FAIL', 'description' => 'Must roll back']);
            $this->fail('Expected the integrity write to fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame($before, DB::table('activity_logs')->count());
        }
    }
}
