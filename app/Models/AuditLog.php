<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    protected $fillable = ['admin_id', 'action', 'target_type', 'target_id', 'details'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** Record an admin action (uses the currently authenticated admin). */
    public static function record(string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
    {
        static::create([
            'admin_id'    => Auth::id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'details'     => $details,
        ]);
    }
}
