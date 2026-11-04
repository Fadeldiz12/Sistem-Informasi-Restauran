{{-- Error validasi di dalam dialog. Pakai: @include('partials.panel.form-errors', ['modal' => 'mUser']) --}}
@if ($errors->any() && old('_modal') === $modal)
  <div class="ferr" role="alert">
    @if ($errors->count() === 1)
      {{ $errors->first() }}
    @else
      <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
  </div>
@endif
