@php($choice = \App\Support\Theme::choice())
<form method="POST" action="{{ route('theme') }}" data-theme-choice data-turbo="false" {{ $attributes->merge(['class' => 'bc-card p-4 stack-3']) }}>
  @csrf
  <div class="flex"><span class="circle-ic"><x-ic n="circle-half-stroke" /></span><div class="grow"><b>Aparência</b><div class="t-caption muted">No automático, segue o tema do celular ou do computador.</div></div></div>
  <div class="bc-seg" role="group" aria-label="Tema">
    @foreach (['auto' => 'Automático', 'claro' => 'Claro', 'escuro' => 'Escuro'] as $v => $l)
      <button class="bc-seg-link grow" name="tema" value="{{ $v }}" aria-current="{{ $choice === $v ? 'true' : 'false' }}">{{ $l }}</button>
    @endforeach
  </div>
</form>
