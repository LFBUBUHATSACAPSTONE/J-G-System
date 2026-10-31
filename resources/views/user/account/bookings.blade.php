{{-- Account > My Bookings. Renders the logged-in user's bookings from
     Account\AccountBookingController (Booking::toAccountArray()).
     Booking shape:
       id, reference, status, status_reason, package{name, price}, event{name, type, type_other,
       location, contact_person, guests, venue_type, start_date, end_date, start_time, end_time},
       payment{method (full|down), state (paid|partial|pending), amount},
       cancellation{status, user_reason, user_cancelled_at, admin_reason, admin_action,
       admin_cancelled_at} (absent when there is no request), can_reschedule (bool),
       reschedule_note (string|null)
     Status labels, tabs and the cancellable list live in config/account.php -> bookings.
     bookings.js filters the tabs, fills the details modal from each card's data-booking JSON and
     posts the cancellation request. No new variable is needed beyond `$bookings`. --}}
@php
$cfg = config('account.bookings', []);
$statuses = $cfg['statuses'] ?? [];
$tabs = $cfg['tabs'] ?? ['all' => 'All'];
$paymentStates = $cfg['payment_states'] ?? [];
$today = \Illuminate\Support\Carbon::today();

$peso = fn ($amount) => 'Php ' . number_format((float) $amount, fmod((float) $amount, 1) ? 2 : 0);

$rows = collect($bookings ?? [])->map(function (array $b) use ($cfg, $statuses, $paymentStates, $today, $peso) {
$status = $b['status'] ?? 'pending';
$meta = $statuses[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'icon' => 'circle', 'tone' => 'neutral', 'group' => 'active'];

$start = \Illuminate\Support\Carbon::parse(data_get($b, 'event.start_date'))->startOfDay();
$end = \Illuminate\Support\Carbon::parse(data_get($b, 'event.end_date') ?? data_get($b, 'event.start_date'))->startOfDay();
$group = $meta['group'] === 'active' ? ($end->lt($today) ? 'past' : 'upcoming') : $meta['group'];

$type = data_get($b, 'event.type');
$typeLabel = $type === 'Others' ? (data_get($b, 'event.type_other') ?: 'Others') : $type;
$date = $start->equalTo($end) ? $start->format('F j, Y') : $start->format('F j, Y') . ' – ' . $end->format('F j, Y');
$time = trim((string) data_get($b, 'event.start_time')) && trim((string) data_get($b, 'event.end_time'))
? data_get($b, 'event.start_time') . ' – ' . data_get($b, 'event.end_time')
: '';

$price = data_get($b, 'package.price');
$method = data_get($b, 'payment.method');
$paid = data_get($b, 'payment.amount');
$methodLabel = $method === 'down' ? 'Down payment' : ($method === 'full' ? 'Full payment' : '');
$stateLabel = $paymentStates[data_get($b, 'payment.state')] ?? '';
// Balance: price minus what was paid. A fully paid booking has nothing left to pay.
$balance = $price !== null && $paid !== null && $method === 'down'
? max((float) $price - (float) $paid, 0)
: ($method === 'full' && data_get($b, 'payment.state') === 'paid' ? 0 : null);

$canCancel = in_array($status, $cfg['cancellable'] ?? [], true) && $start->gt($today);

$payload = [
'id' => (string) $b['id'],
'reference' => $b['reference'],
'status' => $status,
'label' => $meta['label'],
'icon' => $meta['icon'],
'tone' => $meta['tone'],
'group' => $group,
'package' => data_get($b, 'package.name'),
'price' => $price !== null ? $peso($price) : '',
'event' => data_get($b, 'event.name'),
'type' => $typeLabel,
'date' => $date,
'time' => $time,
'location' => data_get($b, 'event.location'),
'contact' => data_get($b, 'event.contact_person'),
'guests' => data_get($b, 'event.guests'),
'venue' => data_get($b, 'event.venue_type'),
'payment' => trim($methodLabel . ($methodLabel && $stateLabel ? ' · ' : '') . $stateLabel) ?: '',
'amount' => $paid !== null && $method ? $peso($paid) : '',
'balance' => $balance !== null ? $peso($balance) : '',
// Why it was cancelled or declined, from `status_reason` on the booking array (null = nothing to show).
// Who gave it: the client for user_cancelled, otherwise J&G. Cancellation requests use the richer
// `cancellation` object below instead (pending / approved / declined / direct admin cancel).
'reason' => in_array($status, ['user_cancelled', 'cancelled', 'admin_cancelled', 'declined'], true) ? ($b['status_reason'] ?? null) : null,
'reason_label' => config("cancellation.labels.{$status}"),
'reason_by' => $status === 'user_cancelled' ? 'You' : 'J&G',
'cancellation' => $b['cancellation'] ?? null,
'can_cancel' => $canCancel,
'can_reschedule' => (bool) ($b['can_reschedule'] ?? false),
'reschedule_note' => $b['reschedule_note'] ?? null,
];

return compact('b', 'meta', 'group', 'payload', 'canCancel', 'typeLabel', 'date', 'time', 'stateLabel', 'methodLabel');
});

$counts = collect(array_keys($tabs))->mapWithKeys(fn ($key) => [$key => $key === 'all' ? $rows->count() : $rows->where('group', $key)->count()]);
$emptyCopy = [
'all' => 'You have no bookings yet.',
'upcoming' => 'No upcoming bookings.',
'past' => 'No past bookings.',
'cancelled' => 'No cancelled bookings.',
];
$tabClass = 'group inline-flex flex-none cursor-pointer items-center gap-2 rounded-full border border-transparent bg-surface px-4 py-2 text-fluid-sm leading-normal font-medium text-ink hover:bg-brand-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [&.is-active]:border-brand-text [&.is-active]:bg-brand-soft [&.is-active]:font-semibold [&.is-active]:text-brand-text';
$tabCountClass = 'min-w-6 rounded-full bg-panel px-2 text-center text-xs leading-6 text-ink-muted group-[.is-active]:bg-surface group-[.is-active]:text-brand-text';
$cardClass = '@container rounded-card bg-surface p-4 text-ink min-[576px]:p-6 [&[hidden]]:hidden';
$emptyClass = '@container flex flex-col items-center gap-3 rounded-card bg-surface px-4 py-8 text-center text-ink min-[576px]:px-6 [&.d-none]:hidden [&>i]:text-[2.5rem] [&>i]:leading-none [&>i]:text-ink-subtle';
$metaClass = 'mt-4 mb-0 grid grid-cols-[minmax(0,1fr)] gap-x-6 gap-y-2 border-t border-dropdown-border pt-4 @min-[32rem]:grid-cols-2 [&>div]:flex [&>div]:min-w-0 [&>div]:items-start [&>div]:gap-2 [&_dt]:flex [&_dt]:flex-none [&_dt]:text-[1.125rem] [&_dt]:leading-normal [&_dt]:text-brand-text [&_dd]:m-0 [&_dd]:text-fluid-sm [&_dd]:[overflow-wrap-anywhere]';
$modalGridClass = 'm-0 grid grid-cols-[minmax(0,1fr)] gap-x-6 gap-y-3 rounded-panel bg-panel p-4 min-[576px]:grid-cols-2 [&_dt]:text-sm [&_dt]:font-medium [&_dt]:text-ink-muted [&_dd]:m-0 [&_dd]:text-fluid-sm [&_dd]:[overflow-wrap-anywhere]';
$statusClass = 'm-0 rounded-panel border border-success-border bg-success-soft px-4 py-3 text-fluid-md font-medium text-success-text [&.is-error]:border-danger-border [&.is-error]:bg-danger-soft [&.is-error]:text-danger-text d-none';
@endphp

<x-account.layout>
  <div
    class="flex max-w-240 flex-col gap-4"
    data-account-bookings
    data-statuses="{{ json_encode($statuses) }}"
    data-cancel-url-template="{{ Route::has('user.account.bookings.cancel') ? route('user.account.bookings.cancel', ['booking' => '__ID__']) : '#' }}"
    data-reschedule-url="{{ route('user.booking') }}">

    <div class="flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Filter bookings">
      @foreach ($tabs as $key => $label)
      <button
        type="button"
        class="{{ $tabClass }}{{ $loop->first ? ' is-active' : '' }}"
        data-bookings-tab="{{ $key }}"
        aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
        <span>{{ $label }}</span>
        <span class="{{ $tabCountClass }}" data-bookings-count="{{ $key }}">{{ $counts[$key] }}</span>
      </button>
      @endforeach
    </div>

    <p class="{{ $statusClass }}" role="status" aria-live="polite" data-bookings-status></p>

    <ul class="m-0 flex list-none flex-col gap-4 p-0 min-[576px]:gap-6" data-bookings-list>
      @foreach ($rows as $row)
      @php
      $b = $row['b'];
      $payload = $row['payload'];
      @endphp
      <li
        class="{{ $cardClass }}"
        data-booking-card
        data-id="{{ $payload['id'] }}"
        data-group="{{ $row['group'] }}"
        data-booking="{{ json_encode($payload) }}">
        <article aria-labelledby="booking-title-{{ $payload['id'] }}">
          <header class="flex flex-col items-start gap-3 @min-[32rem]:flex-row @min-[32rem]:justify-between">
            <div class="min-w-0">
              <h2 class="m-0 text-fluid-xl font-semibold wrap-anywhere" id="booking-title-{{ $payload['id'] }}">{{ $payload['event'] }}</h2>
              <p class="mt-1 mb-0 text-sm text-ink-muted">Booking {{ $payload['reference'] }}</p>
            </div>
            <x-account.badge :tone="$row['meta']['tone']" data-booking-badge>
              <i class="ph ph-{{ $row['meta']['icon'] }}" aria-hidden="true"></i>{{ $row['meta']['label'] }}
            </x-account.badge>
          </header>

          <dl class="{{ $metaClass }}">
            <div>
              <dt><i class="ph ph-calendar-blank" aria-hidden="true"></i><span class="visually-hidden">Date</span></dt>
              <dd>{{ $row['date'] }}</dd>
            </div>
            @if ($row['time'])
            <div>
              <dt><i class="ph ph-clock" aria-hidden="true"></i><span class="visually-hidden">Time</span></dt>
              <dd>{{ $row['time'] }}</dd>
            </div>
            @endif
            <div>
              <dt><i class="ph ph-map-pin" aria-hidden="true"></i><span class="visually-hidden">Location</span></dt>
              <dd>{{ $payload['location'] }}</dd>
            </div>
            <div>
              <dt><i class="ph ph-package" aria-hidden="true"></i><span class="visually-hidden">Package</span></dt>
              <dd>{{ $payload['package'] }}@if ($payload['price']) <span class="ml-1 text-ink-muted">{{ $payload['price'] }}</span>@endif</dd>
            </div>
            @if ($payload['payment'])
            <div>
              <dt><i class="ph ph-wallet" aria-hidden="true"></i><span class="visually-hidden">Payment</span></dt>
              <dd>{{ $payload['payment'] }}@if ($payload['balance'] && (float) preg_replace('/[^0-9.]/', '', $payload['balance']) > 0) <span class="ml-1 text-ink-muted">Balance {{ $payload['balance'] }}</span>@endif</dd>
            </div>
            @endif
          </dl>

          @if (($payload['cancellation']['status'] ?? null) === 'declined')
          <p class="account-booking__note">Your cancellation request was declined. Open View details to see J&amp;G's reason.</p>
          @endif

          @if (($payload['cancellation']['status'] ?? null) === 'declined')
          <p class="account-booking__note">Your cancellation request was declined. Open View details to see J&amp;G's reason.</p>
          @endif

          <footer class="mt-4 flex flex-col-reverse gap-3 @min-[32rem]:flex-row @min-[32rem]:items-center @min-[32rem]:justify-end *:w-full @min-[32rem]:*:w-auto" data-booking-actions>
            <x-account.button
              variant="secondary"
              data-bs-toggle="modal"
              data-bs-target="#bookingDetailsModal"
              data-booking-open>
              View details
            </x-account.button>

            @if ($row['canCancel'])
            <x-account.button variant="danger-outline" data-booking-cancel>
              Request cancellation
            </x-account.button>
            @elseif ($payload['status'] === 'pending_cancellation')
            <p class="account-booking__note">Cancellation request sent. Waiting for J&amp;G to review it.</p>
            @elseif ($payload['status'] === 'user_cancelled' || ($payload['status'] === 'cancelled' && ($payload['cancellation']['status'] ?? null) === 'approved'))
            @if ($payload['can_reschedule'])
            <x-account.button :href="route('user.booking', ['reschedule' => $payload['id']])" data-booking-reschedule>Reschedule</x-account.button>
            @elseif ($payload['reschedule_note'])
            <p class="m-0 text-sm text-ink-muted" data-booking-note>{{ $payload['reschedule_note'] }}</p>
            @endif
            @endif
          </footer>
        </article>
      </li>
      @endforeach
    </ul>

    {{-- One empty message per tab; bookings.js shows the one that matches the active tab. --}}
    @foreach ($tabs as $key => $label)
    <section class="{{ $emptyClass }} d-none" data-bookings-empty="{{ $key }}">
      <i class="ph ph-calendar-blank" aria-hidden="true"></i>
      <p class="m-0 text-fluid-heading font-semibold">{{ $emptyCopy[$key] ?? 'No bookings to show.' }}</p>
      @if (in_array($key, ['all', 'upcoming'], true))
      <x-account.button href="{{ route('user.landing') }}#packages">Browse packages</x-account.button>
      @endif
    </section>
    @endforeach
  </div>

  {{-- Details modal: shared by every card, filled by bookings.js from the card's data-booking JSON. --}}
  <div class="modal fade font-sans" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-card border-0 bg-surface text-ink">
        <div class="modal-header items-start border-0 px-6 pt-6 pb-4">
          <div>
            <h2 class="m-0 text-fluid-xl font-semibold wrap-anywhere" id="bookingDetailsTitle" data-bk="event">Booking details</h2>
            <p class="mt-1 mb-0 text-sm text-ink-muted">Booking <span data-bk="reference"></span></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body flex flex-col gap-6 px-6 py-0">
          <p class="m-0"><span data-bk-badge></span></p>

          {{-- Shown when the booking was cancelled or declined (data-bk-reason); filled by bookings.js. --}}
          <section class="m-0 flex flex-col gap-1 rounded-lg border border-danger-border bg-danger-soft px-4 py-3" aria-labelledby="bk-reason-label" data-bk-reason hidden>
            <h3 class="mb-2 text-sm leading-[1.2] font-semibold text-danger-text" id="bk-reason-label" data-bk-reason-label></h3>
            <p class="mb-4 text-sm text-ink-muted" data-bk-reason-by></p>
            <p class="m-0 text-sm wrap-anywhere whitespace-pre-line text-ink" data-bk-reason-text></p>
          </section>

          {{-- The client's cancellation request and J&G's answer. Filled by bookings.js from
               data.cancellation: pending / approved / declined / directly cancelled by J&G. --}}
          <section class="m-0 flex flex-col gap-1 rounded-lg border border-field-border bg-panel px-4 py-3 [&[hidden]]:hidden" aria-labelledby="bk-cancellation-title" data-bk-cancellation hidden>
            <h3 class="m-0 text-sm leading-[1.2] font-semibold text-ink" id="bk-cancellation-title" data-bk-cancellation-title></h3>
            <p class="m-0 text-sm text-ink-muted" data-bk-cancellation-meta></p>

            <div class="flex flex-col gap-1 pt-2" data-bk-cancellation-user hidden>
              <p class="m-0 text-sm text-ink-muted" data-bk-cancellation-user-label>Your reason</p>
              <p class="m-0 text-sm wrap-anywhere whitespace-pre-line text-ink" data-bk-cancellation-user-reason></p>
              <p class="m-0 text-sm text-ink-muted" data-bk-cancellation-user-date></p>
            </div>

            <div class="flex flex-col gap-1 pt-2" data-bk-cancellation-admin hidden>
              <p class="m-0 text-sm text-ink-muted" data-bk-cancellation-admin-label>J&amp;G's reason</p>
              <p class="m-0 text-sm wrap-anywhere whitespace-pre-line text-ink" data-bk-cancellation-admin-reason></p>
              <p class="m-0 text-sm text-ink-muted" data-bk-cancellation-admin-date></p>
            </div>
          </section>

          <section aria-labelledby="bk-event-title">
            <h3 class="mt-0 mb-3 text-fluid-label font-semibold" id="bk-event-title">Event</h3>
            <dl class="{{ $modalGridClass }}">
              <div>
                <dt>Event type</dt>
                <dd data-bk="type"></dd>
              </div>
              <div>
                <dt>Date</dt>
                <dd data-bk="date"></dd>
              </div>
              <div>
                <dt>Time</dt>
                <dd data-bk="time"></dd>
              </div>
              <div>
                <dt>Location</dt>
                <dd data-bk="location"></dd>
              </div>
              <div>
                <dt>Venue contact</dt>
                <dd data-bk="contact"></dd>
              </div>
              <div>
                <dt>Guests</dt>
                <dd data-bk="guests"></dd>
              </div>
              <div>
                <dt>Venue type</dt>
                <dd data-bk="venue"></dd>
              </div>
            </dl>
          </section>

          <section aria-labelledby="bk-package-title">
            <h3 class="mt-0 mb-3 text-fluid-label font-semibold" id="bk-package-title">Package and payment</h3>
            <dl class="{{ $modalGridClass }}">
              <div>
                <dt>Package</dt>
                <dd data-bk="package"></dd>
              </div>
              <div>
                <dt>Package price</dt>
                <dd data-bk="price"></dd>
              </div>
              <div>
                <dt>Payment</dt>
                <dd data-bk="payment"></dd>
              </div>
              <div>
                <dt>Amount paid</dt>
                <dd data-bk="amount"></dd>
              </div>
              <div>
                <dt>Balance</dt>
                <dd data-bk="balance"></dd>
              </div>
            </dl>
          </section>
        </div>

        <div class="modal-footer flex flex-col-reverse gap-3 border-0 px-6 pt-3 pb-6 min-[576px]:flex-row min-[576px]:justify-end *:m-0 *:w-full min-[576px]:*:w-auto">
          <x-account.button variant="secondary" data-bs-dismiss="modal">Close</x-account.button>
        </div>
      </div>
    </div>
  </div>

  {{-- Request cancellation: the shared action modal with the required reason. bookings.js fills the title and
       message and posts {reason} to user.account.bookings.cancel. --}}
  <x-booking.action-modal id="cancelBookingModal" :reason="true" />
</x-account.layout>