{{-- Linha com interruptor que grava na hora. O botão envia o valor contrário ao atual. --}}
@props(['action', 'name', 'on', 'label', 'hint' => null, 'icon' => null, 'disabled' => false, 'extra' => []])
<form method="POST" action="{{ $action }}" class="bc-toggle-row p-3">
  @csrf
  @foreach ($extra as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
  @if ($icon)<span class="ic-col"><x-ic :n="$icon" /></span>@endif
  <div class="grow t-body-sm"><span class="bold">{{ $label }}</span>@if ($hint)<span class="bc-listrow__hint">{{ $hint }}</span>@endif</div>
  <input type="hidden" name="{{ $name }}" value="{{ $on ? '0' : '1' }}">
  <button type="submit" class="bc-toggle" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $label }}" @disabled($disabled)></button>
</form>
