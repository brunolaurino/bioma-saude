@extends('layouts.pro', ['title' => 'Pedidos de titulares', 'sub' => 'LGPD, art. 18'])
@php use App\Support\{Clock, Fmt}; @endphp
@section('content')
<div class="bc-seg max-md" role="group" aria-label="Situação">
  <a class="bc-seg-link" href="{{ route('pro.requests') }}" @if($open) aria-current="true" @endif>Abertos</a>
  <a class="bc-seg-link" href="{{ route('pro.requests', ['ver' => 'fechados']) }}" @if(! $open) aria-current="true" @endif>Respondidos</a>
</div>
@forelse ($list as $d)
  @php($left = (int) Clock::today()->diffInDays(Clock::local($d->due_at)->startOfDay(), false))
  <article class="bc-card p-4 stack-3 max-lg">
    <div class="flex wrap">
      <x-avatar :name="$d->patient?->name ?? '?'" size="sm" />
      <div class="grow"><b>{{ $d->patient?->name ?? 'Paciente' }}</b><div class="t-caption muted">{{ $types[$d->type] ?? $d->type }} · aberto em {{ Fmt::dmy(Clock::local($d->created_at)) }}</div></div>
      @if ($open)
        <x-pill :tone="$left < 0 ? 'bad' : ($left <= 3 ? 'warn' : null)" icon="clock">{{ $left < 0 ? 'Atrasado '.(-$left).' '.Fmt::plural(-$left, 'dia', 'dias') : 'Prazo: '.Fmt::dm(Clock::local($d->due_at)) }}</x-pill>
      @else
        <x-pill :tone="$d->status === 'atendido' ? 'ok' : 'warn'">{{ $d->status === 'atendido' ? 'Atendido' : 'Negado' }}</x-pill>
      @endif
    </div>
    @if ($d->details)<p class="prose t-body-sm">“{{ $d->details }}”</p>@endif
    @if ($open)
      @if (in_array($d->type, ['acesso', 'portabilidade'], true))<p class="notice"><x-ic n="circle-info" class="ic" /> O paciente consegue baixar todos os dados sozinho em Privacidade &gt; Baixar meus dados. Você pode indicar isso na resposta.</p>@endif
      @if ($d->type === 'eliminacao')<p class="notice notice--warn"><x-ic n="scale-balanced" class="ic" /> O prontuário precisa ser guardado por 20 anos (Lei 13.787/2018). Fotos e desabafos privados já foram apagados se o paciente encerrou a conta. Explique o que fica guardado e por quê.</p>@endif
      <form method="POST" action="{{ route('pro.requests.close', $d) }}" class="stack-3" novalidate>
        @csrf
        <x-textarea name="response" :id="'r-'.$d->id" label="Resposta ao paciente" rows="3" />
        <div class="flex wrap gap-2">
          <button class="bc-btn bc-btn--primary bc-btn--sm" name="status" value="atendido"><x-ic n="check" /> Marcar atendido</button>
          <button class="bc-btn bc-btn--secondary bc-btn--sm" name="status" value="negado"><x-ic n="xmark" /> Negar com justificativa</button>
        </div>
      </form>
    @elseif ($d->response)
      <div class="surface-soft p-3 t-body-sm"><div class="t-caption muted">Resposta em {{ Fmt::dmy(Clock::local($d->closed_at)) }}</div>{{ $d->response }}</div>
    @endif
  </article>
@empty
  <x-empty icon="envelope-open-text" :title="$open ? 'Nenhum pedido aberto' : 'Nenhum pedido respondido'" text="Os pacientes fazem pedidos pelo app, em Privacidade." />
@endforelse
@endsection
