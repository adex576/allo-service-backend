<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function participants() {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function latestMessage() {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Find an existing 1-1 conversation between two users, or create one.
     */
    public static function between(int $userA, int $userB): self
    {
        $conversation = static::whereHas('participants', fn ($q) => $q->where('users.id', $userA))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $userB))
            ->first();

        if (!$conversation) {
            $conversation = static::create();
            $conversation->participants()->attach([$userA, $userB]);
        }

        return $conversation;
    }
}
