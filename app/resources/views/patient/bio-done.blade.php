@extends('layouts.simple', ['title' => 'Registro salvo', 'back' => route('home')])
@php use App\Support\Fmt; @endphp
@section('content')
<div class="center stack-2">
  <span class="circle-ic circle-ic--done"><x-ic n="check" /></span>
  <h2 class="t-title">Bioimpedância registrada</h2>
  <p class="muted">Seus gráficos foram atualizados e {{ $lead }} já pode ver.</p>
</div>
@if ($prev)
<x-section :title="'Comparado com '.Fmt::short($prev['d'])">@include('patient._bio-compare')</x-section>
@endif
<x-section title="Análise de composição corporal"><x-composition :rec="$cur" :prev="$prev" /></x-section>
<div class="grid-2">
  <a class="bc-btn bc-btn--secondary" href="{{ route('home') }}"><x-ic n="house" /> Início</a>
  <a class="bc-btn bc-btn--primary" href="{{ route('evolution') }}"><x-ic n="chart-line" /> Ver gráficos</a>
</div>
@endsection
