<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
        'suspended_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function isClient() {
        return $this->role === 'client';
    }

    public function isPrestataire() {
        return $this->role === 'prestataire';
    }

    public function isAdmin() {
        return $this->role === 'admin';
    }

    public function prestataireProfile() {
        return $this->hasOne(PrestataireProfile::class);
    }

    public function demandes() {
        return $this->hasMany(Demande::class, 'client_id');
    }

    public function offres() {
        return $this->hasMany(Offre::class, 'prestataire_id');
    }

    public function avis() {
        return $this->hasMany(Avis::class, 'client_id');
    }

    public function conversations() {
        return $this->belongsToMany(Conversation::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function sentMessages() {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
