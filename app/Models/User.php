<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
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
        'middle_initial',
        'email',
        'role',
        'google_id',
        'phone',
        'address',
        'password',
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
        ];
    }

    /**
     * New accounts default to the customer role even before the insert (the column default alone
     * would leave the in-memory model without a role until it is refreshed). Admin accounts are
     * created by the owner with an explicit `role` and are never produced by sign-up.
     */
    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->role)) {
                $user->role = self::ROLE_USER;
            }
        });
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

    /**
     * The full middle name (users.middle_name), falling back to the legacy one-letter
     * middle_initial for accounts that were created before that column existed.
     */
    protected function middleName(): Attribute
    {
        return Attribute::get(fn (?string $value): ?string => filled($value) ? $value : $this->middle_initial);
    }

    /**
     * Store a middle name on this account and keep the legacy middle_initial column in sync.
     * Both columns are checked because older databases (and some tests) may not have the new
     * middle_name column yet; the middle initial still remains the source used by the admin side.
     */
    public function setMiddleName(?string $middleName): void
    {
        $middleName = trim((string) $middleName);
        $middleName = $middleName !== '' ? $middleName : null;

        if (Schema::hasColumn($this->getTable(), 'middle_name')) {
            $this->middle_name = $middleName;
        } else {
            $this->middle_name = $middleName;
        }

        if (Schema::hasColumn($this->getTable(), 'middle_initial')) {
            $this->middle_initial = $middleName !== null
                ? mb_strtoupper(mb_substr($middleName, 0, 1))
                : null;
        }
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
