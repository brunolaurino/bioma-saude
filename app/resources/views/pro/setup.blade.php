@extends('layouts.simple', ['title' => 'Configurar o consultório'])
@php use App\Security\Modules; $keys = array_keys($steps); $idx = array_search($step, $keys, true); @endphp
@section('content')
<div class="steps" aria-label="Passo {{ $idx + 1 }} de {{ count($steps) }}">@foreach ($keys as $i => $k)<span class="{{ $i <= $idx ? 'on' : '' }}"></span>@endforeach</div>
<div class="stack-1"><div class="t-overline muted">Passo {{ $idx + 1 }} de {{ count($steps) }}</div><h2 class="t-title">{{ $steps[$step] }}</h2></div>

<form method="POST" action="{{ route('pro.setup.store') }}" class="stack-4" novalidate>
  @csrf<input type="hidden" name="step" value="{{ $step }}">
  @if ($step === 'consultorio')
    <p class="t-body-sm muted">Esses dados aparecem para os pacientes no app e no aviso de privacidade.</p>
    @foreach (['clinic_name', 'clinic_city', 'clinic_contact', 'clinic_hours'] as $k)
      <x-field :name="$k" :label="$fields[$k][0].($k === 'clinic_name' ? ' *' : '')" :icon="$fields[$k][1]" :value="$settings[$k] === 'Consultório' ? '' : $settings[$k]" />
    @endforeach
  @elseif ($step === 'lgpd')
    <p class="t-body-sm muted">Pela LGPD, o consultório é o <b>controlador</b> dos dados dos pacientes e precisa indicar um <b>encarregado</b> (DPO) para atender os titulares. Pode ser você mesmo.</p>
    @foreach (['controller_name', 'controller_document', 'dpo_name', 'dpo_contact'] as $k)
      <x-field :name="$k" :label="$fields[$k][0].(in_array($k, ['controller_name', 'dpo_name', 'dpo_contact'], true) ? ' *' : '')" :icon="$fields[$k][1]" :value="$settings[$k]" />
    @endforeach
  @elseif ($step === 'modulos')
    <p class="t-body-sm muted">Ligue só o que você vai usar. Dá para mudar depois em Configurações, e abrir exceções por paciente.</p>
    <div class="bc-card divide">
      @foreach (Modules::ALL as $k => $l)
        <label class="row-item check">
          <span class="grow"><b>{{ $l }}</b><span class="t-caption muted block">{{ Modules::HINTS[$k] }}</span></span>
          <input type="checkbox" class="toggle-input" name="modules[]" value="{{ $k }}" @checked($modules[$k])>
        </label>
      @endforeach
    </div>
  @else
    <p class="t-body-sm muted">Este é o texto que cada paciente lê e aceita no primeiro acesso. Ele já traz os dados que você preencheu. Revise com calma e, se puder, com seu jurídico.</p>
    <x-textarea name="text" label="Aviso de privacidade" rows="18" :value="$draft" maxlength="60000" />
  @endif
  <div class="flex">
    @if ($idx > 0)<a class="bc-btn bc-btn--secondary" href="{{ route('pro.setup', ['passo' => $keys[$idx - 1]]) }}"><x-ic n="arrow-left" /> Voltar</a>@endif
    <button class="bc-btn bc-btn--primary grow">@if ($step === 'aviso')<x-ic n="check" /> Publicar e começar @else Continuar <x-ic n="arrow-right" />@endif</button>
  </div>
</form>
@endsection
