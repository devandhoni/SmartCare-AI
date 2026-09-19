<?php

namespace App\Models;

use App\Services\ActivityLogIntegrityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    const CREATED_AT = 'created_on';
    const UPDATED_AT = 'updated_on';

    protected $fillable = [
        'user_id',
        'resident_id',
        'module',
        'action',
        'description',
    ];

    protected static function booted(): void
    {
        static::created(function (self $log): void {
            app(ActivityLogIntegrityService::class)->append($log);
        });
    }

    /** Keep the audit insert and its integrity entry in the same transaction. */
    public function save(array $options = [])
    {
        return DB::connection($this->getConnectionName())->transaction(
            fn () => parent::save($options)
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class, 'resident_id');
    }
}
