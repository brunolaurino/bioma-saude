@php($items = array_filter([
  $nav['m']['bioimpedancia'] ? ['weight-scale', 'Bioimpedância', route('bio.create')] : null,
  $nav['m']['fotos'] ? ['camera', 'Fotos de evolução', route('photos.create')] : null,
  $nav['m']['exames'] ? ['file-arrow-up', 'Laudo ou exame', route('exams.create')] : null,
  $nav['m']['atividade'] ? ['dumbbell', 'Treino', route('workouts.create')] : null,
  $nav['m']['saude_mental'] ? ['pen-to-square', 'Desabafo', route('journal.create')] : null,
  $nav['m']['sintomas'] ? ['notes-medical', 'Sintomas', route('symptoms')] : null,
]))
@if ($items)
<div class="grid-3">
  @foreach ($items as [$ic, $label, $href])
    <a class="choice" href="{{ $href }}"><span class="circle-ic circle-ic--lg"><x-ic :n="$ic" /></span>{{ $label }}</a>
  @endforeach
</div>
@else
<p class="muted">Nenhum registro está disponível no seu acompanhamento agora.</p>
@endif
