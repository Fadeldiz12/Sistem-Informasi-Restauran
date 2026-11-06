{{--
  Dialog Batalkan Pesanan (kasir). Tombol pemicu: <button class="js-batal" data-url="..." data-kode="ORD-0001" data-dimasak="1">
--}}
@push('modals')
<div class="ovl" id="mBatal"><form class="modal" method="post" id="fBatal" style="width:min(520px,100%)">
  @csrf
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Batalkan pesanan</div><h3 id="batalKode">ORD-0000</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    <div class="field"><label for="batalAlasan">Alasan pembatalan</label>
      <div class="chips" id="batalChips" style="margin-bottom:10px">@foreach (['Salah input kasir', 'Pesanan dobel', 'Pelanggan batal', 'Menu habis'] as $a)<button type="button" data-a="{{ $a }}">{{ $a }}</button>@endforeach</div>
      <div class="in"><svg class="ic"><use href="#i-edit"/></svg><input id="batalAlasan" name="alasan" maxlength="255" required placeholder="Tulis atau pilih alasan"></div></div>
    <label class="switch-row" for="batalStok" id="batalStokWrap" style="margin-top:14px;display:none"><span>Kembalikan stok bahan<small>Dapur sudah mulai memasak. Centang bila bahan belum terpakai, supaya stok yang terpotong dikembalikan.</small></span><span class="toggle" style="flex:none"><input type="checkbox" name="kembalikan_stok" value="1" id="batalStok"><i></i></span></label>
    <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Tiket pesanan ini dihapus dari antrean dapur. Pembatalan tidak bisa diurungkan.</span></div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Tidak jadi</button><button class="btn" type="submit" id="batalOk"><svg class="ic"><use href="#i-x"/></svg>Batalkan Pesanan</button></div>
</form></div>
@endpush

@push('scripts')
<script>
  (function () {
    document.addEventListener('click', function (e) {
      var b = e.target.closest('.js-batal'); if (!b) return;
      fBatal.action = b.dataset.url; batalKode.textContent = b.dataset.kode;
      batalAlasan.value = ''; batalStok.checked = false; batalOk.disabled = false;
      batalStokWrap.style.display = b.dataset.dimasak === '1' ? 'flex' : 'none';
      openModal('mBatal'); setTimeout(function () { batalAlasan.focus(); }, 50);
    });
    batalChips.addEventListener('click', function (e) { var c = e.target.closest('button'); if (c) { batalAlasan.value = c.dataset.a; } });
    fBatal.addEventListener('submit', function () { batalOk.disabled = true; });
  })();
</script>
@endpush
