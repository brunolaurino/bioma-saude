@extends('layouts.simple', ['title' => 'Desabafo', 'back' => route('mental')])
@php use App\Domain\Catalog; @endphp
@section('content')
<form method="POST" action="{{ route('journal.store') }}" class="stack-5" novalidate>
  @csrf
  <fieldset class="stack-2">
    <legend class="bc-section__title">Como você está?</legend>
    <div class="mood-pick">
      @foreach (Catalog::MOODS as $v => $mood)
        <label><input type="radio" name="mood" value="{{ $v }}" @checked((int) old('mood') === $v)><span><x-ic :n="$mood['icon']" :r="true" /><small>{{ $mood['l'] }}</small></span></label>
      @endforeach
    </div>
    @error('mood')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  </fieldset>
  <fieldset class="stack-2">
    <legend class="bc-section__title">Sobre o quê?</legend>
    <div class="chips">
      @foreach (Catalog::JOURNAL_TAGS as $t)
        <label class="chip-check"><input type="checkbox" name="tags[]" value="{{ $t }}" @checked(in_array($t, old('tags', []), true))><span>{{ $t }}</span></label>
      @endforeach
    </div>
  </fieldset>
  <x-textarea name="text" label="Escreva livremente" :rows="8" placeholder="Como foi o trabalho hoje? O que pesou na rotina? Escreva do seu jeito, sem julgamento." />
  <label class="bc-card p-3 flex share-row">
    <input type="checkbox" name="shared" value="1" class="toggle-input" @checked(old('shared', $shareDefault))>
    <span class="grow t-body-sm"><b>Compartilhar com {{ $lead }}</b><span class="t-caption muted block">Desligado, ninguém da equipe vê este texto.</span></span>
    <span class="bc-toggle" aria-hidden="true"></span>
  </label>
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar desabafo</button>
  <p class="privacy-line muted"><x-ic n="circle-info" /> Se estiver em crise, ligue 188 (CVV, gratuito, 24 horas) ou 192.</p>
</form>
@endsection
