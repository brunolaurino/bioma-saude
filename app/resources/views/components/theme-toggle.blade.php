{{-- Alterna claro/escuro. Sem JavaScript, o servidor chuta o oposto da escolha atual; com ele, vale o tema que está na tela. --}}
<form method="POST" action="{{ route('theme') }}" data-theme-toggle data-turbo="false" {{ $attributes->merge(['class' => 'theme-toggle']) }}>
  @csrf
  <input type="hidden" name="tema" value="{{ \App\Support\Theme::choice() === 'escuro' ? 'claro' : 'escuro' }}">
  <button class="bc-iconbtn" aria-label="Alternar tema claro ou escuro" title="Tema claro ou escuro">
    <x-ic n="moon" class="theme-ic-moon" /><x-ic n="sun" class="theme-ic-sun" />
  </button>
</form>
