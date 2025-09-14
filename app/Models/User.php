<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'furigana',
        'email',
        'password',
        'lineworks_id',
        'lineworks_token',
        'lineworks_refresh_token',
        'icon',
        'phone',
        'mobile_phone',
        'department',
        'position',
        'is_active',
        'hire_date',
        'birth_date',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'is_planner',
        'is_manager',
        'role',
        'affiliation',
        'is_retired',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'lineworks_token',
        'lineworks_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
