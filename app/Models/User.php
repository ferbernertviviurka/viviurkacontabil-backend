<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'permissions' => 'array',
        ];
    }

    /**
     * Check if user has permission for a module.
     */
    public function hasPermission(string $module): bool
    {
        // Master has all permissions
        if ($this->isMaster()) {
            return true;
        }

        // Check specific permissions
        $permissions = $this->permissions ?? [];
        return $permissions[$module] ?? false;
    }

    /**
     * Get the company that the user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the logs for the user.
     */
    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get the AI requests for the user.
     */
    public function aiRequests()
    {
        return $this->hasMany(AiRequest::class);
    }

    /**
     * Check if user is master.
     */
    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    /**
     * Check if user is normal.
     */
    public function isNormal(): bool
    {
        return $this->role === 'normal';
    }
}
