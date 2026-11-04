@props([
'items' => [
['image' => asset('images/carousel/poster1.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster2.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster3.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster4.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster5.webp'), 'alt' => ''],
['image' => asset('images/carousel/6.webp'), 'alt' => ''],
['image' => asset('images/carousel/7.webp'), 'alt' => ''],
],
'interval' => 3000,
])

<div
  class="coverflow position-relative z-1"
  data-coverflow
  data-interval="{{ $interval }}">
  <div class="coverflow__track">
    @foreach ($items as $index => $item)
    <div class="coverflow__slide" data-index="{{ $index }}">
      <div class="coverflow__clip">
        <img
          src="{{ $item['image'] }}"
          alt="{{ $item['alt'] ?? '' }}"
          class="coverflow__img"
          draggable="false">
      </div>
    </div>
    @endforeach
  </div>
</div>