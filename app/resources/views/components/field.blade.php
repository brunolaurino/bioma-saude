@props(['name', 'label', 'icon' => null, 'value' => null, 'type' => 'text', 'hint' => null, 'suffix' => null, 'placeholder' => null, 'inputmode' => null, 'autocomplete' => null, 'required' => false, 'id' => null])
@php
  $id = $id ?? 'f-'.str_replace(['[', ']', '.'], '-', $name);
  $errKey = str_replace(['[', ']'], ['.', ''], $name);
  $error = $errors->first($errKey);
  $val = $type === 'password' ? null : old($errKey, $value);
@endphp
<div class="bc-field {{ $error ? 'bc-field--error' : '' }}">
  <label class="bc-field__label" for="{{ $id }}">{{ $label }}</label>
  <div class="bc-field__box">
    @if ($icon)<span class="bc-field__icon"><x-ic :n="$icon" /></span>@endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $val }}" @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($inputmode) inputmode="{{ $inputmode }}" @endif @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($required) required @endif @if($error || $hint) aria-describedby="{{ $id }}-msg" @endif @if($error) aria-invalid="true" @endif {{ $attributes }}>
    @if ($suffix)<span class="bc-field__suffix">{{ $suffix }}</span>@endif
    {{ $slot }}
  </div>
  @if ($error)
    <span class="bc-field__error" id="{{ $id }}-msg"><x-ic n="circle-exclamation" /> {{ $error }}</span>
  @elseif ($hint)
    <span class="bc-field__hint" id="{{ $id }}-msg">{{ $hint }}</span>
  @endif
</div>
