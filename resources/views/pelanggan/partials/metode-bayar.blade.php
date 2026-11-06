{{--
  Pilihan metode pembayaran (Virtual Account / QRIS / Tunai), dipakai di Checkout dan halaman Pembayaran.
  Variabel: $metode, $bankPilih, $bank (kode => nama), $midtransAktif, $teksTunai
--}}
@use('App\Models\Pembayaran')
<style>
  .opt { position: relative; text-align: left; padding: 18px; border-radius: 0 20px 20px 20px; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m15); margin-top: 12px; width: 100%; }
  .opt::before { content: ''; position: absolute; left: 0; top: -12px; width: 70px; height: 13px; border-radius: 12px 12px 0 0; background: inherit; box-shadow: inset 1.5px 1.5px 0 var(--m15), inset -1.5px 0 0 var(--m15); }
  .opt .top { display: flex; justify-content: space-between; align-items: center; }
  .opt .ico { width: 44px; height: 44px; border-radius: 14px; background: var(--pp); display: grid; place-items: center; }
  .opt .rad { width: 22px; height: 22px; border-radius: 50%; box-shadow: inset 0 0 0 1.8px currentColor; display: grid; place-items: center; }
  .opt b { display: block; font: 700 17px var(--f-title); margin-top: 14px; } .opt small { font: 500 13px var(--f-body); opacity: .75; }
  .opt.on { background: var(--m); color: var(--cr); box-shadow: none; } .opt.on::before { box-shadow: none; }
  .opt.on .ico { background: var(--cr); color: var(--m); } .opt.on .rad::after { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--cr); }
  .opt[disabled] { opacity: .5; cursor: not-allowed; }
  #pay.g3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
  .panel { display: none; margin-top: 18px; } .panel.on { display: block; }
  .banks { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
  .banks button { height: 42px; min-width: 88px; padding: 0 16px; border-radius: 14px; box-shadow: inset 0 0 0 1.5px var(--m15); font: 700 14px var(--f-title); background: var(--cr); }
  .banks button.on { box-shadow: inset 0 0 0 2px var(--m); background: var(--pp); }
  @media (max-width: 640px) { #pay.g3 { grid-template-columns: 1fr; gap: 4px; } }
</style>
<input type="hidden" name="metode" id="metode" value="{{ $metode }}">
<input type="hidden" name="bank" id="bankIn" value="{{ $bankPilih }}">
<div class="g3" id="pay">
  <button type="button" @class(['opt', 'on' => $metode === Pembayaran::METODE_TRANSFER]) data-p="transfer" @disabled(! $midtransAktif)><div class="top"><span class="ico"><svg class="ic"><use href="#i-card"/></svg></span><span class="rad"></span></div><b>Virtual Account</b><small>BCA, BNI, BRI, Permata</small></button>
  <button type="button" @class(['opt', 'on' => $metode === Pembayaran::METODE_QRIS]) data-p="qris" @disabled(! $midtransAktif)><div class="top"><span class="ico"><svg class="ic"><use href="#i-qr"/></svg></span><span class="rad"></span></div><b>QRIS</b><small>Semua m-banking &amp; e-wallet</small></button>
  <button type="button" @class(['opt', 'on' => $metode === Pembayaran::METODE_TUNAI]) data-p="tunai"><div class="top"><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span><span class="rad"></span></div><b>Tunai</b><small id="tunaiSub">{{ $teksTunai }}</small></button>
</div>
@unless ($midtransAktif)
  <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Pembayaran Virtual Account &amp; QRIS belum diaktifkan. Untuk sementara gunakan tunai.</span></div>
@endunless
<div @class(['panel', 'on' => $metode === Pembayaran::METODE_TRANSFER]) data-panel="transfer">
  <div class="eyebrow" style="font-size:10.5px">Pilih bank</div>
  <div class="banks" id="banks">@foreach ($bank as $kode => $nama)<button type="button" data-bank="{{ $kode }}" @class(['on' => $bankPilih === $kode])>{{ $nama }}</button>@endforeach</div>
  <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Nomor Virtual Account dibuat khusus untuk pesanan ini. Tidak perlu unggah bukti, pembayaran dicek otomatis.</span></div>
</div>
<div @class(['panel', 'on' => $metode === Pembayaran::METODE_QRIS]) data-panel="qris">
  <div class="note"><svg class="ic"><use href="#i-info"/></svg><span>Kode QRIS dibuat khusus untuk pesanan ini dan bisa dibayar dari aplikasi bank atau e-wallet apa saja. Tidak perlu unggah bukti.</span></div>
</div>
<div @class(['panel', 'on' => $metode === Pembayaran::METODE_TUNAI]) data-panel="tunai">
  <div class="note"><svg class="ic"><use href="#i-info"/></svg><span><span id="tunaiInfo">{{ $teksTunai }}.</span> Kasir akan mengonfirmasi pesananmu.</span></div>
</div>
<script>
  (function () {
    var pay = document.getElementById('pay');
    pay.addEventListener('click', function (e) {
      var b = e.target.closest('.opt'); if (!b || b.disabled) return;
      document.getElementById('metode').value = b.dataset.p;
      pay.querySelectorAll('.opt').forEach(function (x) { x.classList.toggle('on', x === b); });
      document.querySelectorAll('.panel[data-panel]').forEach(function (x) { x.classList.toggle('on', x.dataset.panel === b.dataset.p); });
    });
    var banks = document.getElementById('banks');
    banks.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      document.getElementById('bankIn').value = b.dataset.bank;
      banks.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
    });
  })();
</script>
