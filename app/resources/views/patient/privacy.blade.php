@extends('layouts.simple', ['title' => 'Privacidade e dados', 'back' => route('profile')])
@php use App\Support\Fmt; @endphp
@section('content')
@if (session('export_token'))
  <div class="notice notice--ok">
    <span class="ic"><x-ic n="file-zipper" /></span>
    <div class="grow t-caption"><b>Seus dados estão prontos</b>Arquivo ZIP com planilhas, um resumo para leitura e seus arquivos. Vale por 24 horas.</div>
    <a data-turbo="false" class="bc-btn bc-btn--primary bc-btn--sm" href="{{ route('privacy.download', session('export_token')) }}"><x-ic n="download" /> Baixar</a>
  </div>
@endif

<x-section title="Suas autorizações">
  <div class="bc-card divide">
    <x-toggle-row :action="route('privacy.consent')" name="value" :on="$shareDefault" label="Compartilhar desabafos por padrão" hint="Você pode mudar em cada texto" icon="feather" :extra="['purpose' => 'journal_share']" />
    <x-toggle-row :action="route('privacy.consent')" name="value" :on="$photos" label="Guardar fotos de evolução" :hint="$photos ? 'Ao desligar, suas fotos são apagadas' : 'Desligado: nenhuma foto é guardada'" icon="camera" :extra="['purpose' => 'photos']" />
  </div>
  <p class="t-caption muted">O tratamento dos dados de saúde para o seu acompanhamento não depende de consentimento: é feito por profissionais de saúde para tutela da sua saúde (LGPD, art. 11, II, f).</p>
</x-section>

<x-section title="Seus direitos">
  <div class="bc-card bc-list divide">
    <x-list-link :href="route('privacy.data')" icon="database" label="Ver que dados vocês têm" hint="Cadastro, contagem de registros e consentimentos" />
    <x-list-link :href="route('privacy.accesses')" icon="eye" label="Quem acessou meus dados" hint="Registro de cada acesso da equipe" />
    <x-list-link :href="route('privacy.request')" icon="envelope-open-text" label="Fazer um pedido" hint="Corrigir, eliminar, saber com quem compartilhamos" />
    <x-list-link :href="route('privacy.notice')" icon="file-contract" label="Aviso de privacidade" :hint="$notice ? 'Versão '.$notice->version.' de '.Fmt::dmy($notice->published_at) : null" />
  </div>
  <form method="POST" action="{{ route('privacy.export') }}" class="mt-2">@csrf
    <button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="file-export" /> Baixar meus dados</button>
  </form>
</x-section>

@if ($requests->isNotEmpty())
<x-section title="Seus pedidos">
  <div class="bc-card divide">
    @foreach ($requests as $r)
      <div class="row-item"><div class="grow"><div class="bold">{{ $types[$r->type] ?? $r->type }}</div><div class="t-caption muted">Aberto em {{ Fmt::dmy($r->created_at) }}{{ $r->status === 'aberto' ? ' · resposta até '.Fmt::dmy($r->due_at) : '' }}</div>@if ($r->response)<p class="t-caption mt-1">{{ $r->response }}</p>@endif</div>
        <x-pill :tone="$r->status === 'atendido' ? 'ok' : ($r->status === 'negado' ? 'bad' : 'warn')" icon="circle">{{ ['aberto' => 'Em análise', 'atendido' => 'Atendido', 'negado' => 'Negado'][$r->status] ?? $r->status }}</x-pill>
      </div>
    @endforeach
  </div>
</x-section>
@endif

<a class="bc-btn bc-btn--danger bc-btn--block" href="{{ route('privacy.delete') }}"><x-ic n="user-xmark" /> Encerrar minha conta</a>
@endsection
