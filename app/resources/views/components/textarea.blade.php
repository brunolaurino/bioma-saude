@props(['name', 'label', 'value' => null, 'rows' => 4, 'placeholder' => null, 'hint' => null, 'id' => null, 'maxlength' => 5000])
@php($id = $id ?? 'f-'.$name) @php($error = $errors->first($name))
<div class="bc-field {{ $error ? 'bc-field--error' : '' }}">
  <label class="bc-field__label" for="{{ $id }}">{{ $label }}</label>
  <div class="bc-field__box">
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" maxlength="{{ $maxlength }}" @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($error || $hint) aria-describedby="{{ $id }}-msg" @endif {{ $attributes }}>{{ old($name, $value) }}</textarea>
  </div>
  @if ($error)
    <span class="bc-field__error" id="{{ $id }}-msg"><x-ic n="circle-exclamation" /> {{ $error }}</span>
  @elseif ($hint)
    <span class="bc-field__hint" id="{{ $id }}-msg">{{ $hint }}</span>
  @endif
</div>
