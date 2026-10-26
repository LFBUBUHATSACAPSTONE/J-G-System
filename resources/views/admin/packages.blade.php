{{-- Admin Packages page. Variable from the route:
       $packages => list of packages
     Cards, modal and buttons read everything from $packages and config/admin/packages.php.
     Nothing is hard-coded, so the stub route can be replaced by a real controller without view edits.
     "Add New Package" is a floating button (bottom right). It is the last thing in <main> so it sits
     after the content in the tab order; the page reserves space under the grid so it never covers a card. --}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="font-sans admin-content admin-packages">
    <div class="mb-6 [&_.admin-page-header]:mb-0">
      <x-admin.page-header />
    </div>

    @if (session('status'))
    <p class="mb-6 rounded-panel border border-success-border bg-success-soft px-6 py-3 font-medium text-success-text data-[tone=danger]:border-danger-border data-[tone=danger]:bg-danger-soft data-[tone=danger]:text-danger-text" role="status">{{ session('status') }}</p>
    @endif
    <p class="mb-6 rounded-panel border border-success-border bg-success-soft px-6 py-3 font-medium text-success-text data-[tone=danger]:border-danger-border data-[tone=danger]:bg-danger-soft data-[tone=danger]:text-danger-text" role="status" data-package-status hidden></p>

    <h2 class="visually-hidden">Packages</h2>

    @if (count($packages) > 0)
    {{-- id: the cards' "See more" toggles use it as data-bs-parent (one open at a time on phones). --}}
    <div class="mb-16 grid grid-cols-1 gap-4 min-[768px]:grid-cols-2 min-[768px]:gap-6 min-[1200px]:grid-cols-3" id="adminPackagesGrid">
      @foreach ($packages as $package)
      <x-admin.package-card :package="$package" />
      @endforeach
    </div>
    @else
    <p class="mt-0 mb-16 px-2 py-6 text-center text-fluid-sm text-ink-muted">No packages yet. Use "Add New Package" to create the first one.</p>
    @endif

    <x-admin.package-modal />

    <button type="button" class="admin-packages__add fixed right-[calc(1.25rem+env(safe-area-inset-right,0px))] bottom-[calc(1.25rem+env(safe-area-inset-bottom,0px))] z-1030 inline-flex min-h-14 min-w-14 cursor-pointer items-center justify-center gap-2 rounded-full border-0 bg-brand p-2 font-semibold text-white shadow-[0_6px_18px_var(--color-shadow)] transition-[background-color,box-shadow] duration-200 hover:bg-brand-hover hover:shadow-[0_8px_24px_var(--color-shadow)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text motion-reduce:transition-none min-[576px]:right-[calc(1.75rem+env(safe-area-inset-right,0px))] min-[576px]:bottom-[calc(1.75rem+env(safe-area-inset-bottom,0px))] min-[576px]:px-6 [&_.ph]:text-fluid-icon" data-bs-toggle="modal" data-bs-target="#packageModal">
      <i class="ph ph-plus" aria-hidden="true"></i>
      <span class="sr-only min-[576px]:not-sr-only">Add New Package</span>
    </button>
  </main>
</body>

</html>