@extends('layouts.simple', ['title' => 'Aviso de privacidade', 'back' => url()->previous() !== url()->current() ? url()->previous() : route('root')])
@section('content')
@if ($notice)
  <p class="t-caption muted">Versão {{ $notice->version }} · publicada em {{ \App\Support\Fmt::dmy($notice->published_at) }}</p>
  <div class="prose">{{ $notice->text }}</div>
@else
  <x-empty icon="file-shield" title="Aviso em preparação" text="O consultório ainda não publicou o aviso de privacidade." />
@endif
@endsection
