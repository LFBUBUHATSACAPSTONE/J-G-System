<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Account > Delete Account (the confirmation modal).
 *
 * NOTHING IS REMOVED FROM THE DATABASE. "Delete" is a soft delete: `users.deleted_at` is set, so the
 * account can no longer log in (every User query now skips it, which also ends any open session), but
 * the user row, the email, the bookings and everything linked to them stay exactly as they were.
 * The email stays reserved, so it cannot be used to sign up again.
 *
 * Contract (resources/js/account/delete.js), DELETE with an empty JSON body:
 *   200 {ok, message, redirect}
 *   409 {message}  the account still has active bookings (cancel them first)
 *   401 {message}  (the `auth` middleware)
 */
class AccountDeletionController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $blocked = DB::transaction(function () use ($userId): bool {
            /** @var User|null $user */
            $user = User::query()->whereKey($userId)->lockForUpdate()->first();

            if (! $user) {
                return false;
            }

            if ($this->hasActiveBookings($user)) {
                return true;
            }

            $user->delete();

            return false;
        });

        if ($blocked) {
            return response()->json([
                'message' => 'You still have active bookings. Cancel them first, then delete your account.',
            ], 409);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'ok' => true,
            'message' => 'Your account has been deleted.',
            'redirect' => route('user.landing'),
        ]);
    }

    /**
     * Active = still awaiting approval, awaiting payment review or confirmed, and the event has not
     * finished yet. Finished events and cancelled / declined / completed bookings never block.
     */
    private function hasActiveBookings(User $user): bool
    {
        $today = Carbon::today()->toDateString();

        return $user->bookings()
            ->whereIn('status', config('account.bookings.cancellable', ['pending', 'pending_payment', 'approved']))
            ->whereHas('schedule', fn ($query) => $query->whereDate('event_end_date', '>=', $today))
            ->exists();
    }
}
