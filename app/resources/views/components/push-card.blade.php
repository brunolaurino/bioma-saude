@php
  // Só aparece com as chaves VAPID configuradas e quando o navegador suporta push (o app.js revela o cartão).
  $hashes = \App\Models\PushSubscription::where('user_id', auth()->id())->pluck('endpoint_hash');
@endphp
@if (\App\Domain\Push::enabled())
<div {{ $attributes->merge(['class' => 'bc-card p-4 push-card']) }} data-push data-key="{{ config('bioma.push.public_key') }}" data-hashes="{{ $hashes->implode(',') }}" hidden>
  <div class="flex">
    <span class="circle-ic"><x-ic n="bell" /></span>
    <div class="grow">
      <b>Notificações no celular</b>
      <div class="t-caption muted" data-push-state>Receba lembretes e avisos mesmo com o app fechado.</div>
    </div>
  </div>
  <p class="t-caption muted mt-2">A mensagem aparece na tela bloqueada, então nunca mostra dados de saúde: só “Você tem uma nova notificação” ou o texto do lembrete.</p>
  <p class="t-caption mt-2" data-push-ios hidden>No iPhone, primeiro adicione o Saúde App à tela de início (Compartilhar › Adicionar à Tela de Início) e abra por lá.</p>
  <p class="t-caption c-bad mt-2" data-push-denied hidden>As notificações estão bloqueadas para este site. Libere nas configurações do navegador.</p>
  <form method="POST" action="{{ route('push.subscribe') }}" data-push-on class="mt-4">
    @csrf
    <input type="hidden" name="endpoint"><input type="hidden" name="p256dh"><input type="hidden" name="auth">
    <button class="bc-btn bc-btn--primary"><x-ic n="bell" /> Ativar notificações</button>
  </form>
  <form method="POST" action="{{ route('push.unsubscribe') }}" data-push-off class="mt-4" hidden>
    @csrf
    <input type="hidden" name="endpoint">
    <button class="bc-btn bc-btn--secondary"><x-ic n="bell-slash" /> Desativar neste aparelho</button>
  </form>
</div>
@endif
