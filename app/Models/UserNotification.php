<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'message', 'link_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Create a notification for a user. */
    public static function pushTo(int $userId, string $type, string $message, ?int $linkId = null): void
    {
        static::create([
            'user_id' => $userId,
            'type'    => $type,
            'message' => $message,
            'link_id' => $linkId,
        ]);
    }
}
