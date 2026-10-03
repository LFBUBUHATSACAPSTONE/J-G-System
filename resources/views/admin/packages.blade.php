{{-- Admin Packages page. Variable from the controller:
       $packages => list of packages (shape in docs/admin-packages.md)

     Cards, modal and buttons read everything from $packages and config/admin-packages.php.

     Nothing is hard-coded, so the stub route can be replaced by a real controller without view edits. 
--}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-packages p-4">
    <div class="admin-packages__top">
      <x-admin.page-header />

      <button type="button" class="admin-packages__add" data-bs-toggle="modal" data-bs-target="#packageModal">
        <i class="ph ph-plus" aria-hidden="true"></i>
        <span>Add New Package</span>
      </button>
    </div>

    @if (session('status'))
    <p class="admin-alert" role="status">{{ session('status') }}</p>
    @endif

    <h2 class="visually-hidden">Packages</h2>

    @if (count($packages) > 0)
    <div class="admin-packages__grid">
      @foreach ($packages as $package)
      <x-admin.package-card :package="$package" />
      @endforeach
    </div>
    @else
    <p class="admin-empty admin-packages__empty">No packages yet. Use "Add New Package" to create the first one.</p>
    @endif

    <x-admin.package-modal />
  </main>
</body>

</html>