@php use App\Domain\Catalog; use App\Support\Fmt; @endphp
@if (! $consent)
  <x-empty icon="shield-halved" title="Sem autorização para fotos" :text="$patient->firstName().' não autorizou o uso de fotos de evolução, ou retirou a autorização. As fotos só aparecem aqui com o consentimento dele.'" />
@elseif ($sessions->isEmpty())
  <x-empty icon="images" title="Nenhuma foto ainda" text="As fotos aparecem aqui quando o paciente enviar." />
@else
  <div class="lg-grid-side stack-6-mobile">
    <x-section title="Comparação lado a lado">
      @if (isset($compare))
        @include('shared.photo-compare', $compare + ['action' => route('pro.patient', $patient), 'hidden' => ['aba' => 'fotos'], 'wide' => true])
      @else
        <p class="muted t-body-sm">A comparação aparece quando houver duas sessões de fotos.</p>
      @endif
    </x-section>
    <x-section title="Todas as sessões">
      <div class="stack-4">
        @foreach ($sessions as $s)
          <div class="stack-1">
            <div class="t-caption bold">{{ Fmt::dmy($s['date']) }}</div>
            <div class="grid-3">
              @foreach (Catalog::ANGLES as $a)
                <x-photo-box class="photo-ph" :src="isset($s['imgs'][$a]) ? route('files.show', [$s['imgs'][$a], 'ver' => 1]) : null" :label="$a" :alt="'Foto '.$a.' de '.Fmt::dm($s['date'])" />
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </x-section>
  </div>
@endif
