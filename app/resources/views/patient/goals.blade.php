@extends('layouts.simple', ['title' => 'Minhas metas', 'back' => route('profile')])
@php use App\Support\Fmt; @endphp
@section('content')
<x-section :title="'Definidas por '.$lead">
  <div class="bc-card divide">
    @if ($goals['weight'] !== null)<div class="row-item"><span class="ic-col"><x-ic n="bullseye" /></span><span class="grow">Peso meta</span><b class="tnum">{{ Fmt::num($goals['weight']) }} kg</b><span class="muted" title="Definida por {{ $lead }}"><x-ic n="lock" /></span></div>@endif
    @if ($goalOn['protein'])<div class="row-item"><span class="ic-col"><x-ic n="drumstick-bite" /></span><span class="grow">Proteína por dia</span><b class="tnum">{{ $goals['protein'] }} g</b><span class="muted"><x-ic n="lock" /></span></div>@endif
    @if ($goalOn['fvl'])<div class="row-item"><span class="ic-col"><x-ic n="carrot" /></span><span class="grow">Frutas, verduras e legumes</span><b>3 grupos</b><span class="muted"><x-ic n="lock" /></span></div>@endif
  </div>
  <p class="t-caption muted">Para mudar essas metas, converse com {{ $lead }} na consulta.</p>
</x-section>
@if ($goalOn['water'])
<form method="POST" action="{{ route('profile.goals') }}" class="stack-4">
  @csrf
  <x-section title="Água por dia">
    <div class="stepper">
      <button type="button" class="bc-btn bc-btn--secondary" data-step="-250" data-target="f-water" aria-label="Menos 250 ml"><x-ic n="minus" /></button>
      <div class="grow center"><input id="f-water" name="water" type="number" inputmode="numeric" min="1000" max="6000" step="250" value="{{ old('water', $goals['water']) }}" class="num-input" aria-label="Meta de água em ml"> <span class="muted">ml</span></div>
      <button type="button" class="bc-btn bc-btn--secondary" data-step="250" data-target="f-water" aria-label="Mais 250 ml"><x-ic n="plus" /></button>
    </div>
    @error('water')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
    @if ($suggest)<p class="t-caption muted center">Recomendação: {{ Fmt::int($suggest) }} ml ({{ $perKg }} ml por kg)</p>@endif
  </x-section>
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar</button>
</form>
@endif
@endsection
