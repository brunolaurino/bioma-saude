@extends('layouts.simple', ['title' => 'Bioimpedância de '.\App\Support\Fmt::short($cur['d']), 'back' => route('bio.index')])
@section('content')
@include('patient._bio-compare', ['full' => true])
<x-section title="Análise de composição corporal"><x-composition :rec="$cur" :prev="$prev" /></x-section>
<x-section title="Laudo anexado">
  @if ($file)
    <a class="bc-card bc-card--action row-item" href="{{ route('files.show', [$file, 'ver' => 1]) }}" target="_blank" rel="noopener">
      <span class="pdf-thumb"><x-ic :n="$file->mime === 'application/pdf' ? 'file-pdf' : 'file-image'" /></span>
      <span class="grow bold truncate">{{ $file->name }}</span><x-ic n="up-right-from-square" class="muted" />
    </a>
  @else
    <x-empty icon="file-circle-plus" title="Sem laudo" :text="'Anexe a foto ou o PDF para '.$lead.' conferir os valores.'" />
  @endif
</x-section>
<form method="POST" action="{{ route('bio.destroy', $record) }}" data-confirm="Excluir este registro?" data-confirm-text="Os gráficos serão recalculados sem essa medição. Não dá para desfazer." data-confirm-label="Excluir">
  @csrf @method('DELETE')
  <button class="bc-btn bc-btn--danger bc-btn--block"><x-ic n="trash-can" /> Excluir registro</button>
</form>
@endsection
