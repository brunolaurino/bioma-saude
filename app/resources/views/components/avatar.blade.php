@props(['name', 'size' => null])
<span class="bc-avatar {{ $size ? 'bc-avatar--'.$size : '' }}" aria-hidden="true">{{ \App\Support\Fmt::initials((string) $name) }}</span>
