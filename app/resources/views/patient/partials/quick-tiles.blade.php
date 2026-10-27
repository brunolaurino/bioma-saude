@php($tiles = array_filter([
  $nav['m']['sintomas'] ? ['notes-medical', 'Sintomas', route('symptoms')] : null,
  $nav['m']['bioimpedancia'] ? ['weight-scale', 'Medir', route('bio.create')] : null,
  $nav['m']['atividade'] ? ['dumbbell', 'Treino', route('workouts.create')] : null,
  $nav['m']['saude_mental'] ? ['pen-to-square', 'Desabafo', route('journal.create')] : null,
  $nav['m']['fotos'] ? ['camera', 'Fotos', route('photos.create')] : null,
  $nav['m']['exames'] ? ['file-arrow-up', 'Exame', route('exams.create')] : null,
]))
@if ($tiles)
<nav class="tiles" aria-label="Registrar agora">
  @foreach ($tiles as [$ic, $label, $href])
    <a class="tile" href="{{ $href }}"><span class="tile__ic"><x-ic :n="$ic" /></span>{{ $label }}</a>
  @endforeach
</nav>
@endif
