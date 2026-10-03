@props([
'packages' => [
[
'name' => 'Budget Lite',
'price' => 5000,
'features' => [
'Ideal for small and intimate events',
'Basic yet clear sound setup',
'Simple lighting for ambience',
'Good for meetings and mini gatherings',
'Easy and quick installation',
],
],
[
'name' => 'Budget Party',
'price' => 10000,
'features' => [
'Perfect for birthdays and school programs',
'Brighter party lighting',
'Improved sound coverage',
'Great for corporate events',
'Fun and lively atmosphere',
],
],
[
'name' => 'Budget Wedding',
'price' => 18000,
'features' => [
'Best for simple weddings',
'LED wall with live feed',
'Clean and elegant audio',
'For church or reception setups',
'Balanced sound and lighting',
],
],
[
'name' => 'Luxe Lite',
'price' => 25000,
'features' => [
'For formal programs and receptions',
'Enhanced lighting setup',
'Clear sound for speeches and music',
'Supports basic band needs',
'Professional presentation finish',
],
],
[
'name' => 'Modern Glam',
'price' => 35000,
'features' => [
'Ideal for debuts and luxury weddings',
'Upgraded visual lighting',
'Strong event audio',
'Works well for indoor venues',
'Grand ambience setting',
],
],
[
'name' => 'Elite Symphony',
'price' => 45000,
'features' => [
'Best for concerts and grand events',
'Full premium audio and lighting',
'Concert-level production',
'Complete event stage setup',
'Maximum visual and sound impact',
],
],
],
])


{{--Package Card Parent Containers--}}
<div id="packages" class="package-selection position-relative z-1" data-package-selection>
    <h2 class="package-selection__title">
        Packages
    </h2>

    <div class="package-selection__grid">
        @foreach ($packages as $package)
        <x-package-card
            :name="$package['name']"
            :price="$package['price']"
            :features="$package['features']"
            :featured="$package['featured'] ?? false" />
        @endforeach
    </div>
</div>