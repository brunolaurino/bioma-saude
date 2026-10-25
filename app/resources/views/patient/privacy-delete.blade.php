@extends('layouts.simple', ['title' => 'Encerrar minha conta', 'back' => route('privacy')])
@section('content')
<div class="notice notice--bad"><span class="ic"><x-ic n="triangle-exclamation" /></span><div class="grow t-caption"><b>Isso não pode ser desfeito</b>Você perde o acesso ao app na hora.</div></div>
<div class="bc-card p-4 stack-2 t-body-sm">
  <p><b>O que acontece:</b></p>
  <ul class="stack-1">
    <li>Seu acesso é encerrado e você sai de todos os aparelhos.</li>
    <li>Suas fotos de evolução e desabafos privados são apagados na hora.</li>
    <li>{{ $lead }} recebe um pedido de eliminação para tratar o restante em até {{ config('bioma.dsr_days') }} dias.</li>
    <li>O prontuário (consultas, medições e anotações clínicas) é guardado pelo prazo exigido por lei, cifrado e sem uso para outra finalidade.</li>
  </ul>
  <p>Se quiser uma cópia antes, <a class="bc-link" href="{{ route('privacy') }}">baixe seus dados</a>.</p>
</div>
<form method="POST" action="{{ route('privacy.delete') }}" class="stack-4" novalidate>
  @csrf
  <x-field name="confirm" label="Digite EXCLUIR para confirmar" icon="keyboard" autocomplete="off" autocapitalize="characters" required />
  <button class="bc-btn bc-btn--danger bc-btn--block"><x-ic n="user-xmark" /> Encerrar minha conta</button>
</form>
@endsection
