@extends('layouts.simple', ['title' => 'Registrar treino', 'back' => route('activity')])
@php use App\Domain\Catalog; @endphp
@section('content')
<form method="POST" action="{{ route('workouts.store') }}" class="stack-5" novalidate>
  @csrf
  <input type="hidden" name="category" value="{{ $cat }}">
  <x-section title="Categoria">
    <div class="grid-2">
      @foreach ([['Força', 'dumbbell', 'Musculação, funcional'], ['Aeróbico', 'heart-pulse', 'Caminhada, corrida, bike']] as [$c, $ic, $h])
        <a class="choice" href="{{ route('workouts.create', ['categoria' => $c]) }}" aria-pressed="{{ $cat === $c ? 'true' : 'false' }}"><x-ic :n="$ic" class="ic-lg" /><b>Treino {{ $c === 'Força' ? 'de Força' : 'Aeróbico' }}</b><small>{{ $h }}</small></a>
      @endforeach
    </div>
  </x-section>
  <fieldset class="stack-2">
    <legend class="bc-section__title">Modalidade</legend>
    <div class="chips">
      @foreach (Catalog::MODALITIES[$cat] as $i => $mod)
        <label class="chip-check"><input type="radio" name="modality" value="{{ $mod }}" @checked(old('modality', Catalog::MODALITIES[$cat][0]) === $mod)><span>{{ $mod }}</span></label>
      @endforeach
    </div>
  </fieldset>
  <x-section title="Duração">
    <div class="stepper">
      <button type="button" class="bc-btn bc-btn--secondary" data-step="-5" data-target="f-minutes" aria-label="Menos 5 minutos"><x-ic n="minus" /></button>
      <div class="grow center"><input id="f-minutes" name="minutes" type="number" inputmode="numeric" min="5" max="600" step="5" value="{{ old('minutes', 45) }}" class="num-input" aria-label="Duração em minutos"> <span class="muted">min</span></div>
      <button type="button" class="bc-btn bc-btn--secondary" data-step="5" data-target="f-minutes" aria-label="Mais 5 minutos"><x-ic n="plus" /></button>
    </div>
    @error('minutes')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  </x-section>
  <fieldset class="stack-2">
    <legend class="bc-section__title">Intensidade</legend>
    <div class="seg-radio">
      @foreach (Catalog::WORKOUT_INTENSITY as $int)
        <label><input type="radio" name="intensity" value="{{ $int }}" @checked(old('intensity', 'Moderada') === $int)><span>{{ $int }}</span></label>
      @endforeach
    </div>
  </fieldset>
  <x-textarea name="note" label="Observação (opcional)" :rows="2" placeholder="Ex.: treino de pernas com a personal" maxlength="500" />
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar treino</button>
</form>
@endsection
