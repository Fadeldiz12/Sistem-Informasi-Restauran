@extends('errors.layout')

@section('kode', '403')
@section('judul', 'Akses Ditolak')
@section('pesan'){{ $exception->getMessage() ?: 'Anda tidak memiliki akses ke halaman ini.' }}@auth Peran akun Anda saat ini: <b>{{ auth()->user()->roleLabel() }}</b>.@endauth @endsection
@section('aksi')
  @auth
    <a href="{{ route(auth()->user()->homeRoute()) }}">Buka halaman saya</a>
  @else
    <a href="{{ route('login') }}">Masuk</a>
  @endauth
@endsection
