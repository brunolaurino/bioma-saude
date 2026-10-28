@extends('layouts.pro', ['title' => 'Agenda', 'sub' => 'Próximos 5 dias'])
@php use App\Support\{Clock, Fmt}; $today = Clock::today(); @endphp
@section('actions')
<a class="bc-btn bc-btn--primary bc-btn--sm" href="#marcar"><x-ic n="calendar-plus" /> <span class="sm-hide">Marcar consulta</span></a>
@endsection
@section('content')
<details class="faq max-md" id="marcar" @if($errors->any()) open @endif>
  <summary><x-ic n="calendar-plus" /> Marcar consulta</summary>
  <form method="POST" action="{{ route('pro.agenda.store') }}" class="stack-4 mt-2" novalidate>
    @csrf
    <div class="bc-field {{ $errors->has('patient_id') ? 'bc-field--error' : '' }}">
      <label class="bc-field__label" for="a-patient">Paciente</label>
      <select id="a-patient" name="patient_id" class="select"><option value="">Escolha…</option>@foreach ($patients as $p)<option value="{{ $p->id }}" @selected(old('patient_id') === $p->id)>{{ $p->name }}</option>@endforeach</select>
      @error('patient_id')<span class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</span>@enderror
    </div>
    <div class="grid-2 gap-3">
      <x-field name="day" label="Data" icon="calendar" inputmode="numeric" placeholder="dd/mm/aaaa" :value="Fmt::dmy($today)" id="a-day" />
      <x-field name="time" label="Horário" icon="clock" inputmode="numeric" placeholder="14:30" id="a-time" />
    </div>
    <div class="bc-field">
      <label class="bc-field__label" for="a-type">Tipo</label>
      <select id="a-type" name="type" class="select">@foreach ($types as $t)<option @selected(old('type', 'Retorno') === $t)>{{ $t }}</option>@endforeach</select>
    </div>
    <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="calendar-check" /> Marcar</button>
  </form>
</details>
@foreach ($days as $d)
  @php($list = $items[$d->toDateString()] ?? collect())
  <x-section :title="($d->equalTo($today) ? 'Hoje' : Fmt::wdl($d)).', '.Fmt::dm($d)">
    @if ($list->isEmpty())
      <p class="notice notice--dashed t-caption muted">Sem consultas</p>
    @else
      <div class="bc-card divide">
        @foreach ($list as $a)
          @php($done = $a->status === 'atendida')
          <div class="row-item wrap {{ $done ? 'is-done' : '' }}">
            <b class="tnum w-12">{{ Fmt::time(Clock::local($a->starts_at)) }}</b>
            <x-avatar :name="$names[$a->patient_id] ?? '?'" size="sm" />
            <a class="grow link-plain" href="{{ route('pro.patient', $a->patient_id) }}"><b>{{ $names[$a->patient_id] ?? 'Paciente' }}</b><div class="t-caption muted">{{ $a->type }}</div></a>
            @if ($done)
              <span class="t-caption muted flex gap-1"><x-ic n="check" /> Atendida</span>
            @else
              <a class="bc-btn bc-btn--primary bc-btn--sm" href="{{ route('pro.patient.prep', $a->patient_id) }}"><x-ic n="clipboard-list" /> Preparar</a>
              <form method="POST" action="{{ route('pro.agenda.status', $a) }}" data-confirm="Cancelar esta consulta?" data-confirm-label="Cancelar consulta">@csrf<input type="hidden" name="status" value="cancelada"><button class="bc-iconbtn" aria-label="Cancelar consulta"><x-ic n="xmark" /></button></form>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </x-section>
@endforeach


@endsection
