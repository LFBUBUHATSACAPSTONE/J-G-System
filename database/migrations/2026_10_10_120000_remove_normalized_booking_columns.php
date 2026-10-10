<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DETAIL_COLUMNS = [
        'booking_events' => [
            'event_name',
            'event_type',
            'event_type_other',
            'event_location',
            'event_contact_person',
            'guest_count',
            'venue_type',
        ],
        'booking_schedules' => [
            'event_start_date',
            'event_end_date',
            'event_start_time',
            'event_end_time',
        ],
        'booking_payments' => [
            'payment_method',
            'payment_state',
            'payment_reference',
            'payment_receipt_path',
            'payment_amount',
        ],
        'booking_packages' => [
            'package_id',
            'package_name',
            'package_price',
        ],
    ];

    public function up(): void
    {
        foreach (self::DETAIL_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot normalize bookings: {$table} does not exist.");
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn('bookings', $column)) {
                    throw new RuntimeException("Cannot normalize bookings: bookings.{$column} does not exist.");
                }
            }

            $this->backfillAndVerify($table, $columns);
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_event_start_date_index');
            $table->dropIndex('bookings_package_id_index');
            $table->dropIndex('bookings_payment_state_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(array_merge(...array_values(self::DETAIL_COLUMNS)));
        });
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve booking data.');
    }

    private function backfillAndVerify(string $table, array $columns): void
    {
        DB::table('bookings')->orderBy('id')->chunk(500, function ($bookings) use ($table, $columns): void {
            $bookingIds = $bookings->pluck('id');
            $details = DB::table($table)
                ->whereIn('booking_id', $bookingIds)
                ->get()
                ->keyBy('booking_id');
            $now = now();

            foreach ($bookings as $booking) {
                $detail = $details->get($booking->id);
                if (! $detail) {
                    $row = ['booking_id' => $booking->id];
                    foreach ($columns as $column) {
                        $row[$column] = $booking->{$column};
                    }
                    $row['created_at'] = $booking->created_at ?? $now;
                    $row['updated_at'] = $booking->updated_at ?? $now;

                    DB::table($table)->insert($row);
                    $detail = (object) $row;
                }

                foreach ($columns as $column) {
                    if ($this->normalize($column, $booking->{$column})
                        !== $this->normalize($column, $detail->{$column})) {
                        throw new RuntimeException(
                            "Cannot normalize bookings: booking {$booking->id} has mismatched {$table}.{$column} data."
                        );
                    }
                }
            }
        });
    }

    private function normalize(string $column, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (str_ends_with($column, '_date')) {
            return substr((string) $value, 0, 10);
        }

        if (in_array($column, ['package_price', 'payment_amount'], true)) {
            return number_format((float) $value, 2, '.', '');
        }

        if ($column === 'guest_count') {
            return (int) $value;
        }

        return (string) $value;
    }
};
