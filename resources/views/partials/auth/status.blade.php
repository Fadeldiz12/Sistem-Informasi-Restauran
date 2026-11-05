@if (session('status'))<div class="alert ok" role="status">{{ session('status') }}</div>@endif
@include('partials.auth-alert')
