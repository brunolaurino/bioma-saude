<dialog class="sheet" id="sheet-install" aria-labelledby="install-title">
  <div class="bc-sheet">
    <div class="bc-sheet__head">
      <span class="c-primary t-title"><x-ic n="mobile-screen-button" /></span>
      <div class="grow">
        <h2 class="bc-sheet__title" id="install-title">Instalar o Saúde App</h2>
        <p class="bc-sheet__text">O app funciona direto do navegador, sem loja de aplicativos.</p>
      </div>
      <button type="button" class="bc-iconbtn" data-close aria-label="Fechar"><x-ic n="xmark" /></button>
    </div>
    <ol class="plain stack-3">
      @foreach ([['arrow-up-from-bracket', 'No Safari (iPhone), toque em Compartilhar.'], ['square-plus', 'Escolha "Adicionar à Tela de Início".'], ['ellipsis-vertical', 'No Chrome (Android), toque no menu e em "Instalar app".'], ['circle-check', 'Pronto: o Saúde App abre em tela cheia, como um aplicativo.']] as $i => [$ic, $t])
        <li class="flex"><span class="bc-avatar bc-avatar--sm">{{ $i + 1 }}</span><span class="grow">{{ $t }}</span><span class="muted"><x-ic :n="$ic" /></span></li>
      @endforeach
    </ol>
  </div>
</dialog>
