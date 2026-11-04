@auth
{{-- Form logout (POST + CSRF). Link/tombol dengan atribut data-logout akan mengirim form ini. --}}
<form id="logout-form" method="post" action="{{ route('logout') }}" hidden>@csrf</form>
<script>
  document.querySelectorAll('[data-logout]').forEach(function (el) {
    el.addEventListener('click', function (e) { e.preventDefault(); document.getElementById('logout-form').submit(); });
  });
</script>
@endauth
