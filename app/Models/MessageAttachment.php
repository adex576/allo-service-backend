<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    protected $fillable = ['message_id', 'path', 'original_name', 'mime_type', 'size'];

    protected $appends = ['url'];

    public function message() {
        return $this->belongsTo(Message::class);
    }

    public function getUrlAttribute(): string {
        return Storage::disk('public')->url($this->path);
    }
}
