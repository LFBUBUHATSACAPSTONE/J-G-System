<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * User account backend (Profile, My Bookings, Security, Delete Account).
 *
 * ADDITIVE ONLY: three nullable columns, nothing is dropped, renamed or rewritten.
 *   users.address     the Profile page's address field
 *   users.deleted_at  "Delete account" is a soft delete: the row, the email and every booking stay
 *                     in the database, the user simply can no longer log in
 *   bookings.user_id  links a booking to the account that made it, so My Bookings can list it
 *
 * Existing bookings are linked to a user only when the booking's client_email matches the email of
 * a VERIFIED user. Bookings that do not match are left untouched (user_id stays NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'address')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->text('address')->nullable();
                });
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }

        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'user_id')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            });
        }

        $this->linkExistingBookingsToUsers();
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve user and booking data.');
    }

    private function linkExistingBookingsToUsers(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('users')
            || ! Schema::hasColumn('bookings', 'user_id') || ! Schema::hasColumn('users', 'email_verified_at')) {
            return;
        }

        DB::table('bookings')
            ->whereNull('user_id')
            ->select('id', 'client_email')
            ->orderBy('id')
            ->chunkById(500, function ($bookings): void {
                $emails = $bookings
                    ->pluck('client_email')
                    ->map(fn ($email) => strtolower(trim((string) $email)))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if ($emails === []) {
                    return;
                }

                $userIds = DB::table('users')
                    ->whereNotNull('email_verified_at')
                    ->whereIn(DB::raw('LOWER(email)'), $emails)
                    ->get(['id', 'email'])
                    ->mapWithKeys(fn ($user) => [strtolower((string) $user->email) => $user->id]);

                foreach ($bookings as $booking) {
                    $userId = $userIds->get(strtolower(trim((string) $booking->client_email)));

                    if ($userId !== null) {
                        DB::table('bookings')
                            ->where('id', $booking->id)
                            ->whereNull('user_id')
                            ->update(['user_id' => $userId]);
                    }
                }
            });
    }
};
