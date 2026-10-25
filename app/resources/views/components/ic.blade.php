@props(['n', 'r' => false])
<i class="{{ $r ? 'fa-regular' : 'fa-solid' }} fa-{{ $n }} {{ $attributes->get('class') }}" aria-hidden="true"></i>
