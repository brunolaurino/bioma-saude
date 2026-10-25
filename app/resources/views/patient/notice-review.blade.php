@extends('layouts.simple', ['title' => 'Aviso de privacidade atualizado'])
@section('content')
<div class="notice notice--warn"><span class="ic"><x-ic n="file-shield" /></span><div class="grow t-caption"><b>O aviso de privacidade mudou</b>Leia a nova versão para continuar usando o app.</div></div>
@if ($notice)
  <p class="t-caption muted">Versão {{ $notice->version }} · {{ \App\Support\Fmt::dmy($notice->published_at) }}</p>
  <div class="prose bc-card p-4 scroll-box">{{ $notice->text }}</div>
@endif
<form method="POST" action="{{ route('notice.accept') }}" class="stack-3">
  @csrf
  <label class="check"><input type="checkbox" name="accept" value="1"><span>Li a nova versão do aviso de privacidade.</span></label>
  @error('accept')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="check" /> Confirmar e continuar</button>
</form>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="bc-btn bc-btn--ghost bc-btn--block"><x-ic n="right-from-bracket" /> Sair</button></form>
@endsection
