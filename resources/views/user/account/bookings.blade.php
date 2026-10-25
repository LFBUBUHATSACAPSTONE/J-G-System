{{-- Account > My Bookings. FRONT END ONLY: it renders whatever `$bookings` it is given (sample data
     from routes/user/account.php today, the logged-in user's bookings from the real controller later).
     Booking shape (same keys as Booking::toAdminArray(), minus `client`):
       id, reference, status, package{name, price}, event{name, type, type_other, location,
       contact_person, guests, venue_type, start_date, end_date, start_time, end_time},
       payment{method (full|down), state (paid|partial|pending), amount},
       can_reschedule (bool), reschedule_note (string|null)
     Status labels, tabs and the cancellable list live in config/account.php -> bookings.
     bookings.js filters the tabs, fills the details modal from each card's data-booking JSON and
     posts the cancel request. No new variable is needed beyond `$bookings`. --}}
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
// Who gave it: the client for user_cancelled, otherwise J&G.
'reason' => in_array($status, ['user_cancelled', 'cancelled', 'declined'], true) ? ($b['status_reason'] ?? null) : null,
'reason_label' => config("cancellation.labels.{$status}"),
'reason_by' => $status === 'user_cancelled' ? 'You' : 'J&G',
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
@endphp

<x-account.layout>
  <div
    class="account-bookings"
    data-account-bookings
    data-statuses="{{ json_encode($statuses) }}"
    data-cancel-url-template="{{ Route::has('user.account.bookings.cancel') ? route('user.account.bookings.cancel', ['booking' => '__ID__']) : '#' }}"
    data-reschedule-url="{{ route('user.booking') }}">

    <div class="account-tabs" role="group" aria-label="Filter bookings">
      @foreach ($tabs as $key => $label)
      <button
        type="button"
        class="account-tabs__btn{{ $loop->first ? ' is-active' : '' }}"
        data-bookings-tab="{{ $key }}"
        aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
        <span>{{ $label }}</span>
        <span class="account-tabs__count" data-bookings-count="{{ $key }}">{{ $counts[$key] }}</span>
      </button>
      @endforeach
    </div>

    <p class="account-form__status is-success d-none" role="status" aria-live="polite" data-bookings-status></p>

    <ul class="account-bookings__list" data-bookings-list>
      @foreach ($rows as $row)
      @php
      $b = $row['b'];
      $payload = $row['payload'];
      @endphp
      <li
        class="account-booking"
        data-booking-card
        data-id="{{ $payload['id'] }}"
        data-group="{{ $row['group'] }}"
        data-booking="{{ json_encode($payload) }}">
        <article aria-labelledby="booking-title-{{ $payload['id'] }}">
          <header class="account-booking__head">
            <div class="account-booking__heading">
              <h2 class="account-booking__title" id="booking-title-{{ $payload['id'] }}">{{ $payload['event'] }}</h2>
              <p class="account-booking__ref">Booking {{ $payload['reference'] }}</p>
            </div>
            <span class="account-badge account-badge--{{ $row['meta']['tone'] }}" data-booking-badge>
              <i class="ph ph-{{ $row['meta']['icon'] }}" aria-hidden="true"></i>{{ $row['meta']['label'] }}
            </span>
          </header>

          <dl class="account-booking__meta">
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
              <dd>{{ $payload['package'] }}@if ($payload['price']) <span class="account-booking__price">{{ $payload['price'] }}</span>@endif</dd>
            </div>
            @if ($payload['payment'])
            <div>
              <dt><i class="ph ph-wallet" aria-hidden="true"></i><span class="visually-hidden">Payment</span></dt>
              <dd>{{ $payload['payment'] }}@if ($payload['balance'] && (float) preg_replace('/[^0-9.]/', '', $payload['balance']) > 0) <span class="account-booking__price">Balance {{ $payload['balance'] }}</span>@endif</dd>
            </div>
            @endif
          </dl>

          <footer class="account-booking__actions" data-booking-actions>
            <button
              type="button"
              class="account-btn account-btn--secondary"
              data-bs-toggle="modal"
              data-bs-target="#bookingDetailsModal"
              data-booking-open>
              View details
            </button>

            @if ($row['canCancel'])
            <button
              type="button"
              class="account-btn account-btn--danger-outline"
              data-booking-cancel>
              Cancel booking
            </button>
            @elseif ($payload['status'] === 'user_cancelled')
            @if ($payload['can_reschedule'])
            <a class="account-btn account-btn--primary" href="{{ route('user.booking', ['reschedule' => $payload['id']]) }}" data-booking-reschedule>Reschedule</a>
            @elseif ($payload['reschedule_note'])
            <p class="account-booking__note" data-booking-note>{{ $payload['reschedule_note'] }}</p>
            @endif
            @endif
          </footer>
        </article>
      </li>
      @endforeach
    </ul>

    {{-- One empty message per tab; bookings.js shows the one that matches the active tab. --}}
    @foreach ($tabs as $key => $label)
    <section class="account-card account-bookings__empty d-none" data-bookings-empty="{{ $key }}">
      <i class="ph ph-calendar-blank" aria-hidden="true"></i>
      <p class="account-bookings__empty-title">{{ $emptyCopy[$key] ?? 'No bookings to show.' }}</p>
      @if (in_array($key, ['all', 'upcoming'], true))
      <a class="account-btn account-btn--primary" href="{{ route('user.landing') }}#packages">Browse packages</a>
      @endif
    </section>
    @endforeach
  </div>

  {{-- Details modal: shared by every card, filled by bookings.js from the card's data-booking JSON. --}}
  <div class="modal fade account-modal" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header account-modal__header">
          <div>
            <h2 class="account-modal__title" id="bookingDetailsTitle" data-bk="event">Booking details</h2>
            <p class="account-modal__sub">Booking <span data-bk="reference"></span></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body account-modal__body">
          <p class="account-modal__status"><span class="account-badge" data-bk-badge></span></p>

          {{-- Shown when the booking was cancelled or declined (data-bk-reason); filled by bookings.js. --}}
          <section class="reason-note" aria-labelledby="bk-reason-label" data-bk-reason hidden>
            <h3 class="reason-note__label" id="bk-reason-label" data-bk-reason-label></h3>
            <p class="reason-note__by" data-bk-reason-by></p>
            <p class="reason-note__text" data-bk-reason-text></p>
          </section>

          <section class="account-modal__section" aria-labelledby="bk-event-title">
            <h3 class="account-modal__heading" id="bk-event-title">Event</h3>
            <dl class="account-modal__grid">
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

          <section class="account-modal__section" aria-labelledby="bk-package-title">
            <h3 class="account-modal__heading" id="bk-package-title">Package and payment</h3>
            <dl class="account-modal__grid">
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

        <div class="modal-footer account-modal__footer">
          <button type="button" class="account-btn account-btn--secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Cancel booking: the shared action modal with the required reason. bookings.js fills the title and
       message and posts {reason} to user.account.bookings.cancel. --}}
  <x-booking.action-modal id="cancelBookingModal" :reason="true" />
</x-account.layout>