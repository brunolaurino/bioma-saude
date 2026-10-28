@extends('layouts.simple', ['title' => 'Efeitos colaterais', 'back' => route('habits')])
@php use App\Domain\Catalog; use App\Support\Fmt; @endphp
@section('content')
<p class="muted">Como você está se sentindo hoje?</p>
<form method="POST" action="{{ route('symptoms') }}" class="stack-5 sym-form" novalidate>
  @csrf
  <fieldset class="stack-3">
    <legend class="bc-section__title">O que você sentiu?</legend>
    <div class="chips">
      @foreach (Catalog::SYMPTOMS as $i => $s)
        <label class="chip-check"><input type="checkbox" id="s-{{ $i }}" name="s[]" value="{{ $i }}" @checked(in_array((string) $i, old('s', []), true) || isset($today[$s]))><span><x-ic n="check" class="when-on" /> {{ $s }}</span></label>
      @endforeach
    </div>
    @error('s')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  </fieldset>
  <fieldset class="stack-2 sym-ints">
    <legend class="bc-section__title">Intensidade</legend>
    <p class="t-caption muted sym-hint">Escolha um sintoma acima para dizer a intensidade.</p>
    @foreach (Catalog::SYMPTOMS as $i => $s)
      @php($cur = old("i.$i", $today[$s]->intensity ?? 'Leve'))
      <div class="bc-card p-3 stack-2 sym-int" id="i-{{ $i }}">
        <div class="bold">{{ $s }}</div>
        <div class="seg-radio" role="radiogroup" aria-label="Intensidade de {{ $s }}">
          @foreach (Catalog::INTENSITY as $int)
            <label><input type="radio" name="i[{{ $i }}]" value="{{ $int }}" @checked($cur === $int)><span>{{ $int }}</span></label>
          @endforeach
        </div>
      </div>
    @endforeach
  </fieldset>
  <x-textarea name="note" label="Quer contar mais? (opcional)" :value="$note" :rows="3" placeholder="Ex.: começou depois do almoço e passou à noite" maxlength="1000" />
  <div class="notice notice--dashed t-caption"><span class="ic"><x-ic n="user-doctor" /></span><span>{{ $lead }} vê esses registros antes da consulta. Sintomas fortes geram um alerta para a equipe. Em caso de urgência, procure atendimento ou ligue 192.</span></div>
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar</button>
</form>
@if ($today->isEmpty())
<form method="POST" action="{{ route('symptoms.fine') }}">@csrf<button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="face-smile" :r="true" /> Estou bem hoje</button></form>
@endif
@if ($past->isNotEmpty())
<x-section title="Registros anteriores">
  <div class="bc-card divide">
    @foreach ($past as $s)
      <div class="row-item"><span class="t-caption muted tnum w-12">{{ Fmt::dm($s->day) }}</span><span class="grow">{{ $s->symptom }}</span><x-pill :tone="Catalog::INT_TONE[$s->intensity]" icon="circle">{{ $s->intensity }}</x-pill></div>
    @endforeach
  </div>
</x-section>
@endif
@endsection
