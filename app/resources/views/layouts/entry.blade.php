@extends('layouts.base')
@section('body')
<div class="entry-bg on-dark">
<x-theme-toggle class="entry-theme" />
<main id="conteudo" class="entry glass">
@yield('content')
</main>
</div>
@endsection
