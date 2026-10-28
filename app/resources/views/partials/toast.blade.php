@php($msg = session('status') ?? session('error'))
@if ($msg)
<div class="toast-wrap" data-toast>
  <div role="status" class="bc-toast {{ session('error') ? 'err' : '' }}">
    <span class="bc-toast__icon"><x-ic :n="session('error') ? 'circle-exclamation' : 'circle-check'" /></span>
    <span>{{ $msg }}</span>
  </div>
</div>
@endif
