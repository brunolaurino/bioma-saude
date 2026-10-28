@php use App\Domain\Indicators; use App\Support\Fmt; $bio = $charts['bio']; @endphp
<div class="lg-grid-2 stack-6-mobile">
  <div>@include('shared.charts', ['charts' => $charts, 'pro' => true, 'url' => fn ($q) => route('pro.patient', [$patient, 'aba' => 'bio'] + $q)])</div>
  <x-section title="Registros do paciente">
    <p class="privacy-line"><x-ic n="wand-magic-sparkles" /> Gráficos gerados a partir dos valores que {{ $patient->firstName() }} digitou no app.</p>
    @if ($bio->isNotEmpty())
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Data</th>@foreach (Indicators::ALL as $i)<th class="num">{{ $i['s'] }}</th>@endforeach<th>Laudo</th></tr></thead>
        <tbody>
          @foreach ($bio->reverse()->values() as $ri => $b)
            @php($pv = $bio->reverse()->values()[$ri + 1] ?? null)
            <tr>
              <td class="bold">{{ Fmt::dm($b['d']) }}<span class="sr-only">/{{ $b['d']->year }}</span></td>
              @foreach (Indicators::ALL as $k => $i)
                @php($tn = $pv ? Indicators::toneOfDelta($b[$k] - $pv[$k], $i['dir']) : null)
                <td class="num {{ $tn ? 'c-'.$tn : '' }}">{{ Fmt::num($b[$k], $i['dec']) }}</td>
              @endforeach
              <td>@if ($b['file_id'] && isset($files[$b['file_id']]))<a data-turbo="false" href="{{ route('files.show', $b['file_id']) }}" aria-label="Baixar laudo de {{ Fmt::dm($b['d']) }}"><x-ic :n="$files[$b['file_id']]->mime === 'application/pdf' ? 'file-pdf' : 'file-image'" /></a>@else<span class="muted">—</span>@endif</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <p class="t-caption muted">Verde: melhorou em relação ao registro anterior. Vermelho: piorou. Âmbar: igual.</p>
    @endif
  </x-section>
</div>
