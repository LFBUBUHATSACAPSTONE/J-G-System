<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add cancellation request and approval fields to bookings table.
 *
 * This migration supports a two-stage cancellation workflow:
 * 1. User submits cancellation request -> status becomes 'pending_cancellation'
 * 2. Admin reviews and either:
 *    a) Approves -> status changes to 'cancelled'
 *    b) Rejects -> status reverts to the status saved in `previous_status`
 * 3. Admin can also cancel directly -> status becomes 'cancelled' and
 *    admin_cancellation_action stays null.
 *
 * The user's reason and the admin's reason are stored in separate columns so
 * the admin's decision never overwrites what the client originally wrote.
 *
 * Fields:
 *   user_cancellation_reason    - Why the user wants to cancel (required from user)
 *   user_cancelled_at           - When the user submitted the cancellation request
 *   admin_cancellation_reason   - Admin's reason (for rejecting a request or cancelling directly)
 *   admin_cancellation_action   - 'approved' | 'rejected' | null (null = admin-initiated)
 *   admin_cancelled_at          - When the admin took action
 *
 * Every column is guarded, so this migration is safe on a database that already
 * received an earlier version of the cancellation work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'user_cancellation_reason')) {
                $table->text('user_cancellation_reason')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('bookings', 'user_cancelled_at')) {
                $table->timestamp('user_cancelled_at')->nullable()->index()->after('user_cancellation_reason');
            }

            if (! Schema::hasColumn('bookings', 'admin_cancellation_reason')) {
                $table->text('admin_cancellation_reason')->nullable()->after('user_cancelled_at');
            }

            if (! Schema::hasColumn('bookings', 'admin_cancellation_action')) {
                $table->enum('admin_cancellation_action', ['approved', 'rejected'])->nullable()->after('admin_cancellation_reason');
            }

            if (! Schema::hasColumn('bookings', 'admin_cancelled_at')) {
                $table->timestamp('admin_cancelled_at')->nullable()->index()->after('admin_cancellation_action');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = array_filter([
                'user_cancellation_reason',
                'user_cancelled_at',
                'admin_cancellation_reason',
                'admin_cancellation_action',
                'admin_cancelled_at',
            ], fn (string $column) => Schema::hasColumn('bookings', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
