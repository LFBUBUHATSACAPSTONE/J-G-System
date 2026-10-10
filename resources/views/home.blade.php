<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head :title="'My Profile | J&G Audio'"></x-site-head>
</head>

<body class="bg-black text-white min-vh-100 d-flex flex-column justify-content-between">
  @include('components.landing.navigation')

  <main class="container my-5 py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="card border border-white border-opacity-25 bg-dark text-white rounded-4 shadow-lg p-4">
          <div class="card-body text-center">
            <div class="mx-auto mb-3 landing-nav__icon-button" style="width: 4.5rem; height: 4.5rem; font-size: 2rem;">
              <x-chat.icon name="user" />
            </div>

            <h2 class="h4 fw-bold mb-1">
              {{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? '' }}
            </h2>
            <p class="text-white-50 mb-4">{{ auth()->user()->email ?? '' }}</p>

            <div class="text-start bg-black bg-opacity-50 p-3 rounded-3 border border-white border-opacity-10 mb-4">
              <div class="d-flex justify-content-between py-2 border-bottom border-white border-opacity-10">
                <span class="text-white-50">First Name</span>
                <span class="fw-medium">{{ auth()->user()->first_name ?? '—' }}</span>
              </div>
              <div class="d-flex justify-content-between py-2 border-bottom border-white border-opacity-10">
                <span class="text-white-50">Last Name</span>
                <span class="fw-medium">{{ auth()->user()->last_name ?? '—' }}</span>
              </div>
              <div class="d-flex justify-content-between py-2 border-bottom border-white border-opacity-10">
                <span class="text-white-50">Email</span>
                <span class="fw-medium">{{ auth()->user()->email ?? '—' }}</span>
              </div>
              <div class="d-flex justify-content-between py-2 border-bottom border-white border-opacity-10">
                <span class="text-white-50">Phone</span>
                <span class="fw-medium">{{ auth()->user()->phone ?? 'Not provided' }}</span>
              </div>
              <div class="d-flex justify-content-between py-2">
                <span class="text-white-50">Account Status</span>
                <span class="badge bg-success">Verified</span>
              </div>
            </div>

            <div class="d-flex gap-3 justify-content-center">
              <a href="{{ route('user.landing') }}" class="btn-color-gradient--secondary font-button--responsive text-pale--white text-decoration-none rounded-3 px-4 py-2 border-0 fw-semibold">
                Back to Home
              </a>
              <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-danger rounded-3 px-4 py-2 fw-semibold">
                  Logout
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  @include('components.landing.footer')
</body>

</html>
