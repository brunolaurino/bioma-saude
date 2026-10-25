@extends(auth()->user()->isPatient() ? 'layouts.simple' : 'layouts.pro', ['title' => 'Aparelhos conectados', 'back' => $back])
@php use App\Support\Fmt; @endphp
@section('content')
@if ($sessions->isEmpty())
  <p class="muted">A lista de aparelhos aparece quando o servidor guarda sessões no banco de dados.</p>
@else
  <div class="bc-card divide">
    @foreach ($sessions as $s)
      <div class="row-item"><span class="circle-ic"><x-ic :n="str_contains($s->agent, 'iPhone') || str_contains($s->agent, 'Android') ? 'mobile-screen' : 'laptop'" /></span>
        <div class="grow"><div class="bold">{{ $s->agent }}</div><div class="t-caption muted">{{ $s->current ? 'Este aparelho' : 'Ativo '.Fmt::rel($s->last) }}</div></div>
        @if ($s->current)<x-pill tone="ok" icon="circle-check">Agora</x-pill> @endif
      </div>
    @endforeach
  </div>
@endif
<form method="POST" action="{{ $action }}" data-confirm="Sair dos outros aparelhos?" data-confirm-text="Quem estiver usando sua conta em outro aparelho vai precisar entrar de novo." data-confirm-label="Sair dos outros">
  @csrf<button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="right-from-bracket" /> Sair de todos os outros aparelhos</button>
</form>
@endsection
