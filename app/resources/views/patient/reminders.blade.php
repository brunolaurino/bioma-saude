@extends('layouts.simple', ['title' => 'Lembretes', 'back' => route('profile')])
@section('content')
<p class="muted">Avisos dentro do app. O texto nunca mostra dados de saúde.</p>
<div class="bc-card divide">
  @foreach ($rows as $k => $r)
    <x-toggle-row :action="route('profile.reminders')" name="enabled" :on="$r['on']" :label="$r['l']" :hint="$r['h']" :icon="$r['icon']" :extra="['key' => $k]" />
  @endforeach
</div>
@endsection
