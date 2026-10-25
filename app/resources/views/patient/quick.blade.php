@extends('layouts.simple', ['title' => 'Registrar', 'back' => route('home')])
@section('content')
<h2 class="t-heading">O que você quer registrar?</h2>
@include('patient.partials.quick-grid')
@endsection
