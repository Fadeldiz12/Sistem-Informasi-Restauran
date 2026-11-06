@auth
{{-- Form logout (POST + CSRF). Link/tombol dengan atribut data-logout akan mengirim form ini. --}}
<form id="logout-form" method="post" action="{{ route('logout') }}" hidden>@csrf</form>
<script>
  // konfirmasi dulu supaya tidak keluar karena salah sentuh (QA A4-#13 / A1-#16); data-langsung = sudah dikonfirmasi lewat dialog panel
  document.querySelectorAll('[data-logout]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      if (!el.hasAttribute('data-langsung') && !confirm('Keluar dari akun?')) return;
      document.getElementById('logout-form').submit();
    });
  });
</script>
@endauth
