@extends(auth()->user()->isPatient() ? 'layouts.simple' : 'layouts.pro', ['title' => 'Trocar senha', 'back' => $back])
@section('content')
<form method="POST" action="{{ $action }}" class="stack-4 max-sm" novalidate>
  @csrf
  <x-field name="password" label="Nova senha" icon="key" type="password" autocomplete="new-password" hint="Mínimo de 10 caracteres. Os outros aparelhos serão desconectados." required />
  <x-field name="password_confirmation" label="Repita a nova senha" icon="key" type="password" autocomplete="new-password" required />
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar nova senha</button>
</form>
@endsection
