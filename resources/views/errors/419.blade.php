@extends('errors.layout')

@section('kode', '419')
@section('judul', 'Sesi Sudah Habis')
@section('pesan', 'Halaman ini terlalu lama dibiarkan terbuka sehingga sesinya habis. Muat ulang halaman, lalu coba lagi.')
@section('aksi')
  <a href="{{ url()->previous() }}">Muat ulang halaman</a>
@endsection
