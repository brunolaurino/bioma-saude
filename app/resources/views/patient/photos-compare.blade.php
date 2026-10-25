@extends('layouts.simple', ['title' => 'Comparar fotos', 'back' => route('evolution', ['aba' => 'fotos'])])
@section('content')
@include('shared.photo-compare', ['action' => route('photos.compare')])
@endsection
