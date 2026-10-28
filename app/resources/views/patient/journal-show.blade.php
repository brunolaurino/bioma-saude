@extends('layouts.simple', ['title' => 'Desabafo', 'back' => route('mental')])
@php use App\Domain\Catalog; use App\Support\Fmt; $m = Catalog::mood((int) $j->mood); @endphp
@section('content')
<p class="t-caption muted">{{ Fmt::wdl($j->day) }}, {{ Fmt::dmy($j->day) }}</p>
<div class="flex"><span class="t-display c-{{ Catalog::moodTone((int) $j->mood) }}"><x-ic :n="$m['icon']" :r="true" /></span><div><b>{{ $m['l'] }}</b>@if ($j->tags)<div class="chips mt-1">@foreach ((array) $j->tags as $t)<span class="bc-tag">{{ $t }}</span>@endforeach</div>@endif</div></div>
<p class="prose">{{ $j->text }}</p>
<x-toggle-row :action="route('journal.share', $j)" name="shared" :on="$j->shared" :label="$j->shared ? 'Compartilhado com '.$lead : 'Privado, só você vê'" :icon="$j->shared ? 'eye' : 'lock'" />
<form method="POST" action="{{ route('journal.destroy', $j) }}" data-confirm="Apagar este desabafo?" data-confirm-text="O texto será removido para você e para a equipe." data-confirm-label="Apagar">
  @csrf @method('DELETE')<button class="bc-btn bc-btn--danger bc-btn--block"><x-ic n="trash-can" /> Apagar</button>
</form>
@endsection
