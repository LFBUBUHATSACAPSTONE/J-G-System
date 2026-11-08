<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /** Customer accounts: the landing page, booking flow, account area and messages. */
    public const ROLE_USER = 'user';

    /** Staff accounts: the admin panel (/admin/*). Created by the owner, never by sign-up. */
    public const ROLE_ADMIN = 'admin';

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->latest('last_message_at');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'role',
        'google_id',
        'phone',
        'address',
        'password',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->role)) {
                $user->role = self::ROLE_USER;
            }
            if (!in_array($user->role, [self::ROLE_USER, self::ROLE_ADMIN], true)) {
                $user->role = self::ROLE_USER;
            }
        });

        static::updating(function (self $user): void {
            if (!in_array($user->role, [self::ROLE_USER, self::ROLE_ADMIN], true)) {
                $user->role = self::ROLE_USER;
            }
        });
    }

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
        ];
    }

    /** True when this account may use the admin panel. */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** True when this account may use the customer area. Legacy rows without a role count as users. */
    public function isUser(): bool
    {
        return ! $this->isAdmin();
    }

    /** Store a middle name on this account. */
    public function setMiddleName(?string $middleName): void
    {
        $middleName = trim((string) $middleName);
        $this->middle_name = $middleName !== '' ? $middleName : null;
    }

    /**
     * Public URL of the profile photo (Admin > Account, users.avatar_path). Null when the account
     * has no photo or the column has not been migrated yet, so the views fall back to initials.
     * Built with asset() (the request root) like Booking::toAdminArray() does for payment proofs,
     * so it also resolves when the app is served from a sub-directory.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->avatar_path)
            ? asset('storage/'.$this->avatar_path)
            : null);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
