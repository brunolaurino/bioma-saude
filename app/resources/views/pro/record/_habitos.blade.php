@php use App\Support\Fmt; $labels = ['water' => ['Água', 'glass-water', 'dos dias com meta batida'], 'protein' => ['Proteína', 'drumstick-bite', 'dos dias com meta batida'], 'fvl' => ['Frutas e verduras', 'carrot', 'dos dias completos']]; @endphp
@if (empty($grid['rows']))
  <x-empty icon="list-check" title="Metas diárias desligadas" text="As metas diárias estão desligadas para este paciente." />
@else
  <div class="bc-card p-4 stack-3">
    <x-habit-grid :grid="$grid" />
    <div class="legend"><span><i class="v1"></i>Meta batida</span><span><i class="v0"></i>Não bateu</span><span><i class="s05"></i>Sintoma moderado</span><span><i class="s1"></i>Sintoma forte</span></div>
  </div>
  <div class="grid-2 lg-grid-4">
    @foreach ($rates as $g => $rate)
      <x-kpi :icon="$labels[$g][1]" :label="$labels[$g][0].' (30 dias)'" :value="$rate.'%'" :hint="$labels[$g][2]" :tone="\App\Domain\ProData::adhTone($rate)" />
    @endforeach
    @if (isset($rates['water']))
      <x-kpi icon="glass-water" label="Água hoje" :value="Fmt::int($today['water']).' ml'" :hint="'meta '.Fmt::int($goals['water']).' ml'" />
    @endif
  </div>
@endif

<x-section title="Metas do paciente">
  @if ($canWrite('metas'))
    <form method="POST" action="{{ route('pro.patient.goals', $patient) }}" class="stack-3 max-lg" novalidate>
      @csrf
      <div class="md-grid-3 stack-4-mobile">
        <x-field name="weight" label="Peso meta" icon="bullseye" suffix="kg" inputmode="decimal" :value="$goals['weight'] ? Fmt::num($goals['weight']) : null" hint="Deixe vazio para não mostrar meta de peso." />
        <x-field name="water" label="Água por dia" icon="glass-water" suffix="ml" inputmode="numeric" :value="$goals['water']" />
        <x-field name="protein" label="Proteína por dia" icon="drumstick-bite" suffix="g" inputmode="numeric" :value="$goals['protein']" />
      </div>
      <button class="bc-btn bc-btn--primary"><x-ic n="floppy-disk" /> Salvar metas</button>
    </form>
  @else
    <div class="bc-card divide max-md">
      <div class="row-item"><span class="ic-col"><x-ic n="bullseye" /></span><span class="grow">Peso meta</span><b class="tnum">{{ $goals['weight'] ? Fmt::num($goals['weight']).' kg' : '—' }}</b></div>
      <div class="row-item"><span class="ic-col"><x-ic n="glass-water" /></span><span class="grow">Água</span><b class="tnum">{{ Fmt::int($goals['water']) }} ml</b></div>
      <div class="row-item"><span class="ic-col"><x-ic n="drumstick-bite" /></span><span class="grow">Proteína</span><b class="tnum">{{ $goals['protein'] }} g</b></div>
    </div>
  @endif
</x-section>
