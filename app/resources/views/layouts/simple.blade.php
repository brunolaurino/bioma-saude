@extends('layouts.base')
@section('body')
<div class="app-patient">
  <x-header :title="$title ?? ''" :back="$back ?? null" />
  <main id="conteudo" class="no-tabs">
    <div class="screen">
      @yield('content')
    </div>
  </main>
</div>
@endsection
