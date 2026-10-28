@props(['w', 'showDate' => false])
@php use App\Support\Fmt; @endphp
<div class="row-item">
  <span class="circle-ic"><x-ic :n="$w->category === 'Força' ? 'dumbbell' : 'heart-pulse'" /></span>
  <div class="grow">
    <div class="bold">{{ $w->modality }} <span class="normal muted">· {{ $w->minutes }} min</span></div>
    <div class="t-caption muted">{{ $showDate ? Fmt::wd($w->day).', '.Fmt::dm($w->day).' · ' : '' }}{{ $w->intensity }}{{ $w->note ? ' · '.$w->note : '' }}</div>
  </div>
  <span class="bc-tag">{{ $w->category }}</span>
</div>
