<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\CurrentAccount;
use App\Services\Auth\SignOut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class AccountDeletionController extends Controller
{
    public function destroy(Request $request, SignOut $signOut, CurrentAccount $account): JsonResponse
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

        // Read the sign-in page BEFORE the sign-out: afterwards nobody is signed in any more.
        $redirect = route($account->customerLoginRoute());

        $signOut->customer($request);

        return response()->json([
            'ok' => true,
            'message' => 'Your account has been deleted.',
            'redirect' => $redirect,
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
