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
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 20)
    {
        return $query->selectRaw("*, ( 6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )) AS distance_km", [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km');
    }

    public function isClient() {
        return $this->role === 'client';
    }

    public function isPrestataire() {
        return $this->role === 'prestataire';
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
}
