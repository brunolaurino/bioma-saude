@extends('layouts.simple', ['title' => 'Ajuda', 'back' => route('profile')])
@section('content')
@php($faq = array_filter([
  $m['bioimpedancia'] ? ['Onde encontro os valores da bioimpedância?', 'No laudo impresso que você recebe após o exame. Procure por Peso, PGC (% de gordura), Massa muscular esquelética, Nível de gordura visceral e TMB.'] : null,
  $m['bioimpedancia'] ? ['Errei um valor. Como corrijo?', 'Abra Evolução, toque em Histórico, escolha o registro e exclua. Depois registre de novo.'] : null,
  $m['saude_mental'] ? ['Quem vê meus desabafos?', 'Só a sua equipe e só os que você marcar como compartilhados. Os privados ficam só na sua conta.'] : null,
  $m['metas'] ? ['Esqueci de marcar as metas de ontem.', 'As metas só podem ser marcadas no próprio dia. Assim '.$lead.' vê seu dia a dia real.'] : null,
  ['Como baixo uma cópia dos meus dados?', 'Em Perfil, Privacidade e dados, toque em Baixar meus dados. O arquivo fica pronto na hora e o link vale por 24 horas.'],
  ['Alguém pode ver meus dados sem eu saber?', 'Não. Todo acesso da equipe fica registrado e você vê a lista em Privacidade e dados, Quem acessou meus dados.'],
]))
@foreach ($faq as [$q, $a])
  <details class="faq"><summary><x-ic n="circle-question" /> {{ $q }}</summary><p>{{ $a }}</p></details>
@endforeach
@if ($contact)
<div class="bc-card p-3 stack-1"><b>Falar com o consultório</b><div class="t-caption muted">Contato: <b class="select-all">{{ $contact }}</b> · {{ $hours }}</div></div>
@endif
<p class="privacy-line muted"><x-ic n="triangle-exclamation" /> Em caso de urgência, não espere a resposta pelo app: ligue 192 (SAMU) ou procure o pronto-socorro.</p>
@endsection
