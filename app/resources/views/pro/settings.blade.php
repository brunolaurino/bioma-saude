@extends('layouts.pro', ['title' => 'Configurações'])
@php use App\Domain\AlertEngine; use App\Models\Professional; use App\Security\Modules; use App\Support\Fmt; use App\Http\Controllers\Pro\SettingsController; @endphp
@section('content')
<a class="bc-card bc-card--action p-4 flex link-plain max-lg" href="{{ route('pro.account') }}">
  <x-avatar :name="$me->name" size="lg" />
  <div class="grow"><b>{{ $me->name }}</b><div class="t-caption muted">{{ $me->specialty }}{{ $master ? ' · master do ambiente' : '' }}</div></div>
  <span class="t-caption bold c-primary flex gap-1">Minha conta <x-ic n="chevron-right" /></span>
</a>

@if (! $master)
  <x-section title="Equipe do consultório" class="max-lg">
    <div class="bc-card divide">
      @foreach ($team as $t)
        <div class="row-item"><x-avatar :name="$t->name" size="sm" /><div class="grow"><b>{{ $t->name }}</b><div class="t-caption muted">{{ $t->specialty }}</div></div>@if ($t->isMaster())<span class="bc-tag">Responsável</span>@endif</div>
      @endforeach
    </div>
    <p class="t-caption muted">As funções do app, as regras de alerta e o acesso de cada profissional são definidos por {{ $team->first(fn ($t) => $t->isMaster())?->name ?? 'o responsável pelo consultório' }}.</p>
  </x-section>
@else
<div class="lg-grid-2 stack-6-mobile">
  <div class="stack-6">
    <x-section title="Funções do app">
      <p class="t-caption muted">Valem para todos os pacientes. Para abrir uma exceção, use a aba Cadastro e acesso no prontuário.</p>
      <div class="bc-card divide">
        @foreach (Modules::ALL as $k => $l)
          <x-toggle-row :action="route('pro.settings.modules')" name="value" :on="$modules[$k]" :label="$l" :hint="Modules::HINTS[$k]" :extra="['module' => $k]" />
          @if ($k === 'metas' && $modules['metas'])
            @foreach (Modules::GOALS as $g => $gl)
              <x-toggle-row :action="route('pro.settings.goals')" name="value" :on="$goals[$g]" :label="'↳ '.$gl" :extra="['goal' => $g]" />
            @endforeach
          @endif
        @endforeach
      </div>
    </x-section>

    <x-section title="Regras de alerta" id="regras">
      <div class="bc-card divide">
        @foreach (AlertEngine::RULES as $k => $r)
          <x-toggle-row :action="route('pro.settings.rules')" name="value" :on="$rules[$k]" :label="$r['l']" :hint="$r['h']" :icon="$r['icon']" :extra="['rule' => $k]" />
        @endforeach
        <form method="POST" action="{{ route('pro.settings.rules') }}" class="row-item">
          @csrf<input type="hidden" name="rule" value="idle"><input type="hidden" name="value" value="1">
          <span class="ic-col"><x-ic n="calendar-xmark" /></span>
          <label class="grow t-body-sm bold" for="rule-idle">Paciente sem registro há</label>
          <select id="rule-idle" name="days" class="select w-auto" data-autosubmit>@foreach (AlertEngine::IDLE_OPTIONS as $d)<option value="{{ $d }}" @selected($idleDays === $d)>{{ $d }} dias</option>@endforeach</select>
          <noscript><button class="bc-btn bc-btn--secondary bc-btn--sm">Salvar</button></noscript>
        </form>
      </div>
    </x-section>

    <x-section title="Metas padrão para novos pacientes">
      <form method="POST" action="{{ route('pro.settings.defaults') }}" class="stack-3" novalidate>
        @csrf
        <div class="grid-3 gap-3">
          <x-field name="water_ml_per_kg" label="Água" suffix="ml/kg" inputmode="numeric" :value="$settings['water_ml_per_kg']" />
          <x-field name="protein_g_per_kg" label="Proteína" suffix="g/kg" inputmode="decimal" :value="$settings['protein_g_per_kg']" />
          <x-field name="bio_interval_days" label="Bioimpedância" suffix="dias" inputmode="numeric" :value="$settings['bio_interval_days']" />
        </div>
        <button class="bc-btn bc-btn--secondary"><x-ic n="floppy-disk" /> Salvar metas padrão</button>
      </form>
    </x-section>
  </div>

  <div class="stack-6">
    <x-section title="Equipe">
      <div class="bc-card divide">
        @foreach ($team as $t)
          @php($st = $t->user?->status)
          <div class="row-item wrap">
            <x-avatar :name="$t->name" size="sm" />
            <div class="grow"><b>{{ $t->name }}</b><div class="t-caption muted">{{ $t->specialty }}{{ $t->council_number ? ' · '.$t->council.' '.$t->council_number : '' }}</div></div>
            @if ($t->isMaster())
              <span class="bc-tag">Master</span>
            @else
              <x-pill :tone="$st === 'active' ? 'ok' : ($st === 'blocked' ? 'bad' : 'warn')">{{ ['active' => 'Ativo', 'blocked' => 'Bloqueado', 'pending' => 'Convite pendente'][$st] ?? $st }}</x-pill>
              <form method="POST" action="{{ route('pro.settings.member', $t->user_id) }}" @if($st === 'active') data-confirm="Bloquear {{ $t->name }}?" data-confirm-text="A pessoa sai do painel na hora e não consegue entrar de novo até você desbloquear." data-confirm-label="Bloquear" @endif>
                @csrf
                @if ($st === 'pending')<button class="bc-btn bc-btn--ghost bc-btn--sm" name="action" value="reinvite"><x-ic n="rotate" /> Novo link</button>
                @elseif ($st === 'active')<button class="bc-btn bc-btn--ghost bc-btn--sm" name="action" value="block"><x-ic n="ban" /> Bloquear</button>
                @else<button class="bc-btn bc-btn--ghost bc-btn--sm" name="action" value="unblock"><x-ic n="unlock" /> Desbloquear</button>@endif
              </form>
            @endif
          </div>
        @endforeach
      </div>
      @if (session('member_link'))
        <div class="notice notice--ok"><x-ic n="link" class="ic" /><div class="grow"><b>Link de cadastro (vale 7 dias)</b><span class="select-all break">{{ session('member_link') }}</span></div><button type="button" class="bc-btn bc-btn--secondary bc-btn--sm" data-copy="{{ session('member_link') }}"><x-ic n="copy" /> Copiar</button></div>
      @endif
      <details class="faq" @if($errors->hasAny(['name', 'email', 'specialty', 'council_number'])) open @endif>
        <summary><x-ic n="user-plus" /> Convidar profissional</summary>
        <form method="POST" action="{{ route('pro.settings.team') }}" class="stack-3 mt-2" novalidate>
          @csrf
          <x-field name="name" label="Nome" icon="user" />
          <x-field name="email" label="E-mail" icon="envelope" type="email" />
          <div class="grid-2 gap-3">
            <div class="bc-field">
              <label class="bc-field__label" for="m-spec">Especialidade</label>
              <select id="m-spec" name="specialty" class="select">@foreach (array_keys(Professional::SPECIALTIES) as $s)<option @selected(old('specialty', 'Nutrição') === $s)>{{ $s }}</option>@endforeach</select>
            </div>
            <x-field name="council_number" label="Registro no conselho" icon="id-badge" />
          </div>
          <p class="t-caption muted">A pessoa recebe um link por e-mail, cria a senha e ativa a verificação em duas etapas. Depois você escolhe em quais pacientes ela entra.</p>
          <button class="bc-btn bc-btn--primary"><x-ic n="paper-plane" /> Enviar convite</button>
        </form>
      </details>
    </x-section>

    <x-section title="Consultório e LGPD">
      <form method="POST" action="{{ route('pro.settings.clinic') }}" class="stack-3" novalidate>
        @csrf
        @foreach (SettingsController::CLINIC_FIELDS as $k => [$label, $icon])
          <x-field :name="$k" :label="$label" :icon="$icon" :value="$settings[$k]" />
        @endforeach
        <button class="bc-btn bc-btn--secondary"><x-ic n="floppy-disk" /> Salvar dados</button>
      </form>
    </x-section>

    <x-section title="Aviso de privacidade">
      <p class="t-body-sm">Versão atual: <b>{{ $notice ? 'v'.$notice->version.', publicada em '.Fmt::dmy($notice->published_at) : 'nenhuma publicada' }}</b></p>
      <details class="faq">
        <summary><x-ic n="file-pen" /> Revisar e publicar nova versão</summary>
        <form method="POST" action="{{ route('pro.settings.notice') }}" class="stack-3 mt-2" novalidate>
          @csrf
          <x-textarea name="text" label="Texto do aviso" rows="16" :value="$notice->text ?? $draft" maxlength="60000" hint="Os campos do consultório entram no modelo. Revise com seu jurídico antes de publicar." />
          <label class="check"><input type="checkbox" name="relevant" value="1" checked> <span>Mudança relevante: pedir que os pacientes leiam e aceitem de novo no próximo acesso.</span></label>
          <button class="bc-btn bc-btn--primary"><x-ic n="upload" /> Publicar nova versão</button>
        </form>
      </details>
    </x-section>

    <x-section title="LGPD e segurança">
      <div class="bc-card divide">
        <x-list-link :href="route('pro.requests')" icon="envelope-open-text" label="Pedidos de titulares" hint="Responder em até 15 dias" />
        <x-list-link :href="route('pro.audit')" icon="clipboard-list" label="Auditoria" hint="Quem acessou o quê, com verificação de integridade" />
        <x-list-link :href="route('pro.incidents')" icon="shield-virus" label="Incidentes de segurança" hint="Registro exigido pela ANPD" />
      </div>
    </x-section>
  </div>
</div>
@endif
@endsection
