@extends('layouts.simple', ['title' => 'Nutrição', 'back' => route('care')])
@section('content')
<p class="t-caption muted">{{ $who }}</p>
@include('shared.nutrition', ['upload' => route('nutrition.plan')])
@endsection
