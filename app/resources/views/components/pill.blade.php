@props(['tone' => null, 'icon' => null])
@php($t = \App\Domain\Indicators::TONE[$tone] ?? null)
<span class="bc-pill {{ $tone ? 'bc-pill--'.$tone : '' }}">@if ($icon || $t)<x-ic :n="$icon ?? $t['icon']" />@endif {{ trim($slot) !== '' ? $slot : ($t['label'] ?? '') }}</span>
