@php use App\Support\{Clock, Fmt}; @endphp
<x-section title="Plano alimentar atual">
  @if ($current)
    <div class="bc-card p-3 stack-3 border-strong">
      <div class="flex">
        <span class="pdf-thumb"><x-ic :n="$current->mime === 'application/pdf' ? 'file-pdf' : 'file-image'" /></span>
        <div class="grow"><div class="bold truncate">{{ $current->name }}</div><div class="t-caption muted">Enviado em {{ Fmt::dmy($current->taken_on) }}{{ isset($names[$current->uploaded_by]) ? ' por '.$names[$current->uploaded_by] : '' }} · {{ Fmt::size($current->size) }}</div></div>
      </div>
      <a data-turbo="false" class="bc-btn bc-btn--primary bc-btn--block" href="{{ route('files.show', [$current, 'ver' => 1]) }}" target="_blank" rel="noopener"><x-ic n="eye" /> Abrir</a>
    </div>
  @else
    <x-empty icon="file-circle-plus" title="Nenhum plano anexado" text="O plano alimentar aparece aqui quando for enviado." />
  @endif
  @if ($upload)
    <form method="POST" action="{{ $upload }}" enctype="multipart/form-data" class="flex mt-2">
      @csrf
      <label class="bc-btn bc-btn--secondary file-btn grow"><x-ic n="file-arrow-up" /> <span data-file-name>{{ $current ? 'Enviar nova versão' : 'Anexar PDF' }}</span><input type="file" name="arquivo" accept="application/pdf,image/jpeg,image/png" required data-file-label></label>
      <button class="bc-btn bc-btn--primary"><x-ic n="floppy-disk" /> Salvar</button>
    </form>
    @error('arquivo')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  @endif
  @if ($old->isNotEmpty())
    <details class="faq mt-2">
      <summary>Versões anteriores ({{ $old->count() }})</summary>
      <ul class="plain stack-2 mt-2">@foreach ($old as $p)<li class="flex t-body-sm"><x-ic n="file-pdf" /><a data-turbo="false" class="grow truncate bc-link" href="{{ route('files.show', [$p, 'ver' => 1]) }}" target="_blank" rel="noopener">{{ $p->name }}</a><span class="muted">{{ Fmt::dm($p->taken_on) }}</span></li>@endforeach</ul>
    </details>
  @endif
</x-section>
<x-section title="Histórico de consultas">
  @if ($consults->isEmpty() && $upcoming->isEmpty())
    <p class="muted t-body-sm">Nenhuma consulta registrada ainda.</p>
  @else
  <ol class="consult-list">
    @foreach ($upcoming as $a)
      @php($local = Clock::local($a->starts_at))
      <li class="upcoming"><div class="flex gap-2 wrap"><b>{{ $a->type }}</b><span class="t-caption muted">{{ Fmt::dmy($local) }} · {{ Fmt::time($local) }}</span>@if ($local->isSameDay(Clock::today()))<x-pill>Hoje</x-pill> @else<x-pill>Agendada</x-pill> @endif</div></li>
    @endforeach
    @foreach ($consults as $c)
      <li><div class="flex gap-2 wrap"><b>{{ $c->type }}</b><span class="t-caption muted">{{ Fmt::dmy($c->day) }}{{ isset($names[$c->professional_id]) ? ' · '.$names[$c->professional_id] : '' }}</span></div>@if ($c->note)<p class="t-caption muted max-text">{{ $c->note }}</p>@endif</li>
    @endforeach
  </ol>
  @endif
</x-section>
