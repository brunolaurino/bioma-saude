@php use App\Support\Fmt; @endphp
@if ($exams->isEmpty())
  <x-empty icon="file-medical" title="Nenhum exame anexado" text="Laudos e exames que o paciente anexar aparecem aqui." />
@else
  <div class="bc-card divide max-lg">
    @foreach ($exams as $e)
      <div class="row-item">
        <span class="pdf-thumb"><x-ic :n="$e->mime === 'application/pdf' ? 'file-pdf' : 'file-image'" /></span>
        <div class="grow"><div class="bold truncate">{{ $e->label ?: $e->name }}</div><div class="t-caption muted">{{ $e->kind === 'laudo' ? 'Laudo de bioimpedância' : 'Exame' }} · {{ Fmt::dmy($e->taken_on) }} · {{ Fmt::size($e->size) }}</div></div>
        @if (str_starts_with($e->mime, 'image/'))<a data-turbo="false" class="bc-iconbtn" href="{{ route('files.show', [$e, 'ver' => 1]) }}" target="_blank" rel="noopener" aria-label="Ver"><x-ic n="eye" /></a>@endif
        <a data-turbo="false" class="bc-iconbtn" href="{{ route('files.show', $e) }}" aria-label="Baixar"><x-ic n="download" /></a>
      </div>
    @endforeach
  </div>
  <p class="privacy-line"><x-ic n="lock" /> Os arquivos ficam cifrados. Cada abertura fica registrada na auditoria.</p>
@endif
