@extends('layouts.pro', ['title' => 'Novo paciente', 'sub' => 'Envie um convite para o app', 'back' => route('pro.patients')])
@php use App\Domain\Catalog; use App\Http\Controllers\Pro\RecordController; use App\Security\Modules; @endphp
@section('content')
<form method="POST" action="{{ route('pro.patients.store') }}" class="stack-6 max-lg" novalidate>
  @csrf
  <div class="md-grid-2 stack-4-mobile">
    <x-field name="name" label="Nome completo *" icon="user" autocomplete="off" required />
    <x-field name="email" label="E-mail *" icon="envelope" type="email" autocomplete="off" hint="O convite vai para este e-mail." required />
    <x-field name="phone" label="Celular" icon="phone" inputmode="tel" placeholder="(11) 90000-0000" />
    <x-field name="birth_date" label="Nascimento" icon="cake-candles" inputmode="numeric" placeholder="dd/mm/aaaa" />
  </div>

  <fieldset class="stack-2">
    <legend class="bc-section__title">Objetivo</legend>
    <div class="chips">
      @foreach (Catalog::OBJECTIVES as $o)
        <label class="chip-check"><input type="radio" name="objective" value="{{ $o }}" @checked(old('objective', 'Emagrecimento') === $o)><span>{{ $o }}</span></label>
      @endforeach
    </div>
  </fieldset>

  <fieldset class="stack-2">
    <legend class="bc-section__title">Metas iniciais</legend>
    <div class="md-grid-3 stack-4-mobile">
      <x-field name="weight" label="Peso meta" icon="bullseye" suffix="kg" inputmode="decimal" placeholder="68,0" />
      <x-field name="water" label="Água por dia" icon="glass-water" suffix="ml" inputmode="numeric" value="2500" :hint="'Sugestão: '.$perKg.' ml por kg'" />
      <x-field name="protein" label="Proteína por dia" icon="drumstick-bite" suffix="g" inputmode="numeric" value="100" :hint="'Sugestão: '.$protKg.' g por kg'" />
    </div>
  </fieldset>

  @if ($team->isNotEmpty())
  <fieldset class="stack-2">
    <legend class="bc-section__title">Equipe multidisciplinar</legend>
    <p class="t-caption muted">Cada profissional vê só as partes do prontuário da especialidade dele. Você ajusta depois em Cadastro e acesso.</p>
    <div class="bc-card divide">
      @foreach ($team as $t)
        <label class="row-item check">
          <span class="ic-col"><x-ic :n="$t->icon()" /></span>
          <span class="grow"><b>{{ $t->name }}</b><span class="t-caption muted block">{{ $t->specialty }} · vê {{ collect(RecordController::defaultModules($t->specialty))->map(fn ($m) => mb_strtolower(Modules::ALL[$m]))->join(', ', ' e ') }}</span></span>
          <input type="checkbox" class="toggle-input" name="team[{{ $t->user_id }}]" value="1" @checked(old('team.'.$t->user_id, true))>
        </label>
      @endforeach
    </div>
  </fieldset>
  @endif

  <fieldset class="stack-3">
    <legend class="bc-section__title">Menor de idade</legend>
    <label class="check"><input type="checkbox" name="is_minor" value="1" @checked(old('is_minor'))> <span>O paciente tem menos de 18 anos. O tratamento dos dados precisa do consentimento de um dos pais ou do responsável legal (LGPD, art. 14).</span></label>
    <x-field name="guardian_name" label="Nome do responsável legal" icon="user-shield" />
  </fieldset>

  <div class="stack-2">
    <button class="bc-btn bc-btn--primary"><x-ic n="paper-plane" /> Cadastrar e enviar convite</button>
    <p class="t-caption muted">Nome e e-mail são obrigatórios. Na próxima tela aparece o código para mandar também por WhatsApp.</p>
  </div>
</form>
@endsection
