@extends('layouts.simple', ['title' => 'Anexar exame', 'back' => route('evolution', ['aba' => 'exames'])])
@php use App\Domain\Catalog; use App\Support\{Clock, Fmt}; @endphp
@section('content')
<form method="POST" action="{{ route('exams.store') }}" enctype="multipart/form-data" class="stack-4" novalidate>
  @csrf
  <fieldset class="stack-2">
    <legend class="bc-section__title">Tipo</legend>
    <div class="chips">
      @foreach (Catalog::EXAM_KINDS as $k)
        <label class="chip-check"><input type="radio" name="label" value="{{ $k }}" @checked(old('label', 'Bioimpedância') === $k)><span>{{ $k }}</span></label>
      @endforeach
    </div>
  </fieldset>
  <x-field name="taken_on" label="Data do exame" icon="calendar" :value="Fmt::dmy(Clock::today())" inputmode="numeric" placeholder="dd/mm/aaaa" required />
  <label class="bc-upload file-pick">
    <input type="file" name="arquivo" accept="image/jpeg,image/png,application/pdf" required data-file-label>
    <span class="bc-upload__icon"><x-ic n="cloud-arrow-up" /></span>
    <span class="bold" data-file-name>Toque para escolher a foto ou o PDF</span>
    <span class="t-caption muted">JPG, PNG ou PDF até 15 MB</span>
  </label>
  @error('arquivo')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar</button>
</form>
@endsection
