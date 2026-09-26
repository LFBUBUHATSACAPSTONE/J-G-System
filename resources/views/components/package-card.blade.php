@props([
    'name',
    'price',
    'features' => [],
])

<div {{ $attributes->merge(['class' => 'package-card bg-dark rounded-4 p-4']) }}>
    <h3 class="text-white fw-semibold fs-5 mb-4">{{ $name }}</h3>
    <p class="text-white fw-bold display-6 mb-4">Php {{ number_format($price) }}</p>

    <x-button
        class="w-100 btn-light fw-semibold py-3 rounded-3"
        data-bs-toggle="modal"
        data-bs-target="#authModal"
        data-auth-view="login"
    >
        Book Now
    </x-button>

    <hr class="border-secondary my-4">

    <h4 class="text-white fw-semibold mb-2">Features</h4>
    <ul class="list-unstyled text-light small">
        @foreach($features as $feature)
            <li class="mb-2">• {{ $feature }}</li>
        @endforeach
    </ul>
</div>