<?php

namespace App\Services\Notifications;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingWorkflowNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class BookingWorkflowNotifier
{
    public function notifyAdmins(int|Booking $booking, string $event): void
    {
        $booking = $booking instanceof Booking ? $booking : Booking::find($booking);
        if (! $booking) {
            return;
        }

        $admins = User::query()->where('role', User::ROLE_ADMIN)->get();
        $this->send($admins, $event, $booking);
    }

    public function notifyUser(int|Booking $booking, string $event): void
    {
        $booking = $booking instanceof Booking
            ? $booking->loadMissing('user')
            : Booking::with('user')->find($booking);
        $user = $booking?->user;

        if (! $booking || ! $user || ! $user->isUser()) {
            return;
        }

        $this->send(collect([$user]), $event, $booking);
    }

    /** Notifications for this authenticated recipient newer than the supplied cursor. */
    public function updatesFor(User $user, ?Carbon $since): array
    {
        if (! $since) {
            return [];
        }

        try {
            return $user->notifications()
                ->where('type', BookingWorkflowNotification::class)
                // Database timestamps are commonly stored at whole-second precision. A
                // one-second overlap plus browser-side id de-duplication avoids boundary loss.
                ->where('created_at', '>=', $since->copy()->subSecond())
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn ($notification) => [
                    'id' => (string) $notification->id,
                    ...$notification->data,
                ])
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Booking workflow notifications could not be loaded.', [
                'exception' => $exception::class,
            ]);

            return [];
        }
    }

    private function send(\Illuminate\Support\Collection $recipients, string $event, Booking $booking): void
    {
        try {
            Notification::send($recipients, new BookingWorkflowNotification($event, $booking));
        } catch (Throwable $exception) {
            // Notification storage must never turn a committed booking action into an API failure.
            Log::warning('Booking workflow notification could not be stored.', [
                'event' => $event,
                'booking_id' => $booking->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
