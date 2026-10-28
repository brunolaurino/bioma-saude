@extends('layouts.pro', ['title' => 'Minha conta', 'back' => route('pro.settings')])
@php use App\Support\Fmt; @endphp
@section('content')
<div class="bc-card p-4 flex max-lg">
  <x-avatar :name="$pro->name" size="lg" />
  <div class="grow"><b>{{ $pro->name }}</b><div class="t-caption muted">{{ $pro->specialty }}{{ $pro->council_number ? ' · '.$pro->council.' '.$pro->council_number : '' }}</div><div class="t-caption muted">{{ $user->email }}</div></div>
  <x-pill :tone="$pro->isMaster() ? 'ok' : null" icon="user-shield">{{ $pro->isMaster() ? 'Master' : 'Equipe' }}</x-pill>
</div>

<x-section title="Segurança" class="max-lg">
  <div class="bc-card divide">
    <div class="row-item"><span class="ic-col"><x-ic n="shield-halved" /></span><div class="grow"><b>Verificação em duas etapas</b><div class="t-caption muted">Ativa desde {{ Fmt::dmy($user->two_factor_confirmed_at) }} · {{ $recoveryLeft }} {{ Fmt::plural($recoveryLeft, 'código', 'códigos') }} de recuperação sobrando</div></div><x-pill tone="ok" icon="circle-check">Ativa</x-pill></div>
    <x-list-link :href="route('pro.account.password')" icon="key" label="Trocar senha" hint="Pede sua senha atual antes" />
  </div>
</x-section>

<x-section title="Aparelhos conectados" class="max-lg">
  @if ($sessions->isEmpty())
    <p class="muted t-body-sm">A lista de aparelhos aparece quando o servidor guarda sessões no banco de dados.</p>
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
  <form method="POST" action="{{ route('pro.account.sessions') }}" data-confirm="Sair dos outros aparelhos?" data-confirm-text="Quem estiver usando sua conta em outro aparelho vai precisar entrar de novo." data-confirm-label="Sair dos outros">
    @csrf<button class="bc-btn bc-btn--secondary"><x-ic n="right-from-bracket" /> Sair de todos os outros aparelhos</button>
  </form>
</x-section>

<x-section title="Notificações" class="max-lg"><x-push-card /></x-section>

<form method="POST" action="{{ route('logout') }}" class="max-lg">@csrf<button class="bc-btn bc-btn--ghost bc-btn--block c-bad"><x-ic n="right-from-bracket" /> Sair do painel</button></form>
@endsection
