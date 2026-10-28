{{-- Diálogo de confirmação usado por formulários com data-confirm (ver public/js/app.js). --}}
<dialog class="sheet" id="confirm-sheet" aria-labelledby="confirm-title">
  <div class="bc-sheet">
    <div class="bc-sheet__head">
      <span class="c-bad" data-confirm-icon><x-ic n="triangle-exclamation" /></span>
      <div class="grow">
        <h2 class="bc-sheet__title" id="confirm-title"></h2>
        <p class="bc-sheet__text" data-confirm-text></p>
      </div>
    </div>
    <div class="bc-sheet__actions">
      <button type="button" class="bc-btn bc-btn--secondary" data-close><x-ic n="xmark" /> Voltar</button>
      <button type="button" class="bc-btn bc-btn--danger" data-confirm-ok><x-ic n="check" /> <span data-confirm-label>Confirmar</span></button>
    </div>
  </div>
</dialog>
