@php use App\Domain\Catalog; use App\Security\Modules; @endphp
<div class="lg-grid-2 stack-6-mobile">
  <div class="stack-6">
    <x-section title="Cadastro">
      <form method="POST" action="{{ route('pro.patient.data', $patient) }}" class="stack-3" novalidate>
        @csrf
        <x-field name="name" label="Nome completo" icon="user" :value="$patient->name" />
        <x-field name="phone" label="Celular" icon="phone" inputmode="tel" :value="$patient->phone" />
        <div class="bc-field">
          <label class="bc-field__label" for="g-objective">Objetivo</label>
          <select id="g-objective" name="objective" class="select"><option value="">Não informado</option>@foreach (Catalog::OBJECTIVES as $o)<option @selected(old('objective', $patient->objective) === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="bc-field">
          <label class="bc-field__label" for="g-status">Situação</label>
          <select id="g-status" name="status" class="select">
            @foreach (['active' => 'Em acompanhamento', 'invited' => 'Convidado (ainda não entrou)', 'closing' => 'Encerrando (acesso bloqueado, dados guardados)', 'closed' => 'Encerrado'] as $k => $l)
              <option value="{{ $k }}" @selected(old('status', $patient->status) === $k)>{{ $l }}</option>
            @endforeach
          </select>
          <span class="bc-field__hint">Encerrar bloqueia o acesso do paciente. O prontuário continua guardado pelo prazo legal.</span>
        </div>
        <button class="bc-btn bc-btn--primary"><x-ic n="floppy-disk" /> Salvar cadastro</button>
      </form>
    </x-section>

    @if ($pendingInvite)
    <x-section title="Convite">
      <div class="notice notice--warn"><x-ic n="envelope" class="ic" /><div class="grow"><b>Conta ainda não ativada</b>Gerar um código novo invalida o anterior.</div></div>
      <form method="POST" action="{{ route('pro.patient.invite', $patient) }}">@csrf<button class="bc-btn bc-btn--secondary"><x-ic n="rotate" /> Gerar novo código</button></form>
    </x-section>
    @endif

    <x-section title="Equipe deste paciente">
      <p class="t-caption muted">Marque o que cada profissional pode ver. Desmarcar tudo tira a pessoa da equipe deste paciente.</p>
      @forelse ($members as $mb)
        @php($granted = $team[$mb->user_id]->modules ?? [])
        <form method="POST" action="{{ route('pro.patient.team', $patient) }}" class="bc-card p-3 stack-3">
          @csrf<input type="hidden" name="professional_id" value="{{ $mb->user_id }}">
          <div class="flex"><x-avatar :name="$mb->name" size="sm" /><div class="grow"><b>{{ $mb->name }}</b><div class="t-caption muted">{{ $mb->specialty }}</div></div>@if ($granted)<x-pill tone="ok">Na equipe</x-pill>@endif</div>
          <div class="chips">
            @foreach (Modules::ALL as $k => $l)
              @continue(in_array($k, ['lembretes', 'conquistas', 'equipe', 'agenda'], true))
              <label class="chip-check"><input type="checkbox" name="modules[]" value="{{ $k }}" @checked(in_array($k, $granted, true))><span><x-ic n="check" class="when-on" /> {{ $l }}</span></label>
            @endforeach
          </div>
          <button class="bc-btn bc-btn--secondary bc-btn--sm"><x-ic n="floppy-disk" /> Salvar acesso</button>
        </form>
      @empty
        <p class="muted t-body-sm">Nenhum profissional na equipe do consultório. Convide em Configurações.</p>
      @endforelse
    </x-section>
  </div>

  <x-section title="Funções do app para {{ $patient->firstName() }}">
    <p class="t-caption muted">Por padrão o paciente segue a configuração do consultório. Aqui você abre uma exceção só para ele.</p>
    <div class="bc-card divide">
      @foreach (Modules::ALL as $k => $l)
        @php($ov = $overrides[$k]) @php($state = $ov === null ? 'default' : ($ov ? 'on' : 'off'))
        <form method="POST" action="{{ route('pro.patient.modules', $patient) }}" class="p-3 stack-2">
          @csrf<input type="hidden" name="module" value="{{ $k }}">
          <div class="between"><span><b>{{ $l }}</b><span class="t-caption muted block">Padrão do consultório: {{ $defaults[$k] ? 'ligado' : 'desligado' }}</span></span>
            <x-pill :tone="$m[$k] ? 'ok' : null" :icon="$m[$k] ? 'circle-check' : 'circle-minus'">{{ $m[$k] ? 'Ligado' : 'Desligado' }}</x-pill></div>
          <div class="bc-seg" role="group" aria-label="{{ $l }}">
            @foreach (['default' => 'Padrão', 'on' => 'Ligar', 'off' => 'Desligar'] as $sv => $sl)
              <button class="bc-seg-link" name="state" value="{{ $sv }}" @if($state === $sv) aria-current="true" @endif>{{ $sl }}</button>
            @endforeach
          </div>
        </form>
      @endforeach
    </div>
  </x-section>
</div>
