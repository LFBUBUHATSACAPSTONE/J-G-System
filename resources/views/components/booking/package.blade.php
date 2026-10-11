@props(['packages' => []])


{{--Package Card Parent Containers--}}
<div id="packages" class="package-selection position-relative z-1" data-package-selection>
    <h2 class="package-selection__title">
        Packages
    </h2>

    <div class="package-selection__grid" id="package-grid">
        @foreach ($packages as $package)
        <x-package-card
            :name="$package['name']"
            :price="$package['price']"
            :features="$package['features']"
            :package-id="$package['id']"
            :featured="$package['featured'] ?? false" />
        @endforeach
    </div>
</div>