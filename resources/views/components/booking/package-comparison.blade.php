@props([
'title' => 'Package Comparison Table',
'packages' => [
    ['name' => 'Budget Lite',    'price' => 5000],
    ['name' => 'Budget Party',   'price' => 10000],
    ['name' => 'Budget Wedding', 'price' => 18000],
    ['name' => 'Luxe Lite',      'price' => 25000],
    ['name' => 'Modern Glam',    'price' => 35000],
    ['name' => 'Elite Symphony', 'price' => 45000],
],
'sections' => [
    [
        'title' => 'Audio System',
        'rows' => [
            ['Audio System',              ['Basic', 'Mid Setup', 'Premium', 'Full Band Ready', 'Full Band Ready', 'Full Band + Stage']],
            ['Main Speaker (FOH)',        ['2 Units', '2 Units + Subwoofer', '2 Units', '4 Units + Subwoofer', '2 Units + Subwoofer', '4 Units + Subwoofer']],
            ['Wireless Microphones',      ['2', '2', '4', '4', '4', '4']],
            ['Wired Microphones',         [null, '2', '2', '4', '4', '4']],
            ['Mixer',                     ['Mackie ProFX16', 'Mackie ProFX16', 'Mackie ProFX16', 'Mackie DL16SE', 'Mackie DL16SE', 'Mackie DL16SE']],
            ['DJ Controller + Laptop',    [true, true, true, true, true, true]],
            ['DI Box / Band Instruments', [null, null, null, 'Full band Set', 'Full band Set', 'Full band Set']],
            ['Cables & Accessories',      [true, true, true, true, true, true]],
        ],
    ],
    [
        'title' => 'Lighting System',
        'rows' => [
            ['Basic Ambient Lights',      [true, true, true, true, true, true]],
            ['Par LED Effect',            [null, true, '18 Units', '20 Units', '20 Units', '20 Units']],
            ['Moving Head / Beam Lights', [null, '4 Beam 260', '4 Beam 260', '4 Beam 450', '6 Beam 450', '8 Beam 450 + MAC aura']],
            ['Fog / Smoke Machine',       ['1', '2', '1', '2 + Haze', '2 + Haze', '2 + Haze']],
            ['Lighting Controller',       ['1', '2', '2', 'Mini Pearl', 'Mini Pearl', 'Mini Pearl']],
            ['Truss / Stands',            ['Basic', true, 'Truss', '6 Truss', '6 Truss', '6 Truss']],
        ],
    ],
    [
        'title' => 'Video System',
        'rows' => [
            ['LED Wall Display',           [null, null, 'P3', '9×12', '9×12 GTOP', '9×12 GTOP']],
            ['Live Feed Camera',           [null, null, true, true, 'Panasonic', 'Panasonic']],
            ['Video Processor / Switcher', [null, null, true, true, true, true]],
        ],
    ],
],
])

<section class="pcmp position-relative z-1">
  <h2 class="pcmp__title">{{ $title }}</h2>

  @foreach ($sections as $section)
  <div class="pcmp__section">
    <h3 class="pcmp__section-title">{{ $section['title'] }}</h3>

    <div class="pcmp__scroll">
      <table class="pcmp__table">
        <thead>
          <tr>
            <th class="pcmp__label">Features / Equipment</th>
            @foreach ($packages as $package)
            <th>
              <span class="pcmp__pkg">{{ $package['name'] }}</span>
              <span class="pcmp__pkg">Php {{ number_format((float) $package['price']) }}</span>
            </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach ($section['rows'] as [$label, $values])
          <tr>
            <th class="pcmp__label">{{ $label }}</th>
            @foreach ($values as $value)
            <td>
              @if ($value === true)
              <svg class="pcmp__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
              @elseif ($value)
              {{ $value }}
              @endif
            </td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endforeach
</section>

<style>
  .pcmp { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem 3rem; color: #f2f2f2; }
  .pcmp__title { margin: 0 0 1.75rem; font-size: 1.6rem; font-weight: 700; text-transform: uppercase; }
  .pcmp__section { margin-bottom: 2.75rem; }
  .pcmp__section-title { margin: 0 0 1rem; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }
  .pcmp__scroll { background: #1c1c1c; border-radius: 0.75rem; padding: 0.5rem 1.5rem 0.75rem; overflow-x: auto; }
  .pcmp__table { width: 100%; min-width: 820px; border-collapse: collapse; font-size: 0.7rem; text-align: center; }
  .pcmp__table th, .pcmp__table td { padding: 0.85rem 0.5rem; vertical-align: middle; font-weight: 400; color: #cfcfcf; }
  .pcmp__table thead tr { border-bottom: 1px solid rgba(255,255,255,0.12); }
  .pcmp__table tbody tr + tr { border-top: 1px solid rgba(255,255,255,0.12); }
  .pcmp__label { width: 18%; text-align: left !important; font-weight: 600 !important; color: #f2f2f2 !important; }
  .pcmp__pkg { display: block; font-weight: 600; color: #f2f2f2; line-height: 1.3; }
  .pcmp__check { width: 1rem; height: 1rem; color: #f2f2f2; vertical-align: middle; }
</style>