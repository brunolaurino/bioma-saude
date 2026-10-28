@extends('layouts.simple', ['title' => 'Registrar bioimpedância', 'back' => route('evolution')])
@php use App\Domain\Indicators; use App\Support\{Clock, Fmt}; @endphp
@section('content')
<p class="muted">Digite os 5 valores do seu laudo.@if ($last) Os valores do último registro aparecem como dica.@endif</p>
<form method="POST" action="{{ route('bio.store') }}" enctype="multipart/form-data" class="stack-4" novalidate>
  @csrf
  <x-field name="measured_on" label="Data da medição" icon="calendar" :value="Fmt::dmy(Clock::today())" inputmode="numeric" placeholder="dd/mm/aaaa" required />
  @foreach (Indicators::ALL as $k => $ind)
    <x-field :name="'v['.$k.']'" :label="$ind['l']" :icon="$ind['icon']" inputmode="decimal" :placeholder="$ind['ph']" :suffix="$ind['u']" :hint="$last ? 'Último: '.Fmt::num($last[$k], $ind['dec']).' '.$ind['u'].' em '.Fmt::short($last['d']) : null" required />
  @endforeach
  <x-section title="Foto ou PDF do laudo (opcional)">
    <div class="grid-2">
      <label class="bc-upload file-pick">
        <input type="file" name="laudo" accept="image/jpeg,image/png,application/pdf" data-file-label>
        <span class="bc-upload__icon"><x-ic n="paperclip" /></span>
        <span data-file-name>Fotografar ou escolher arquivo</span>
        <span class="t-caption muted">JPG, PNG ou PDF até 15 MB</span>
      </label>
    </div>
    @error('laudo')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  </x-section>
  @if ($errors->any())<p class="bc-field__error"><x-ic n="triangle-exclamation" /> Confira os campos destacados.</p>@endif
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar registro</button>
</form>
@endsection
