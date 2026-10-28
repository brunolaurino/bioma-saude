@extends('layouts.simple', ['title' => 'Fotos de evolução', 'back' => route('evolution', ['aba' => 'fotos'])])
@php use App\Domain\Catalog; @endphp
@section('content')
@if (! $consent)
  <div class="bc-card p-4 stack-3">
    <h2 class="t-heading"><x-ic n="shield-halved" /> Autorização para fotos</h2>
    <p class="t-body-sm">Fotos do corpo são dados sensíveis. Para guardar suas fotos de evolução, precisamos da sua autorização específica.</p>
    <ul class="t-body-sm stack-1">
      <li>As fotos ficam cifradas e só você e {{ $lead }} podem ver.</li>
      <li>Elas servem só para acompanhar sua evolução, nunca para divulgação.</li>
      <li>Você pode retirar a autorização quando quiser em Privacidade. Ao retirar, as fotos são apagadas.</li>
    </ul>
    <form method="POST" action="{{ route('photos.consent') }}" class="stack-3">
      @csrf
      <label class="check"><input type="checkbox" name="accept" value="1"><span>Autorizo guardar minhas fotos de evolução para o acompanhamento.</span></label>
      @error('accept')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
      <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="check" /> Autorizar e continuar</button>
    </form>
  </div>
@else
  <div class="notice notice--dashed t-caption"><span class="ic"><x-ic n="lightbulb" /></span><span><b>Para comparar melhor</b>Mesmo lugar e mesma luz, celular na altura do peito, cerca de 2 metros de distância, roupa justa.</span></div>
  <form method="POST" action="{{ route('photos.store') }}" enctype="multipart/form-data" class="stack-4">
    @csrf
    <div class="grid-3">
      @foreach (Catalog::ANGLES as $a)
        <label class="angle-pick photo-tall">
          <input type="file" name="foto[{{ $a }}]" accept="image/jpeg,image/png" capture="environment" data-file-label aria-label="Foto {{ $a }}">
          <x-ic :n="Catalog::ANGLE_ICON[$a]" class="big" />
          <span class="t-caption bold"><x-ic n="camera" /> {{ $a }}</span>
          <span class="picked t-caption"><x-ic n="circle-check" /> escolhida</span>
        </label>
      @endforeach
    </div>
    @foreach ($errors->get('foto*') as $msgs)@foreach ((array) $msgs as $msg)<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $msg }}</p>@endforeach @endforeach
    <p class="t-caption muted">Fotos HEIC do iPhone: use Ajustes &gt; Câmera &gt; Formatos &gt; Mais compatível.</p>
    <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar fotos</button>
  </form>
@endif
@endsection
