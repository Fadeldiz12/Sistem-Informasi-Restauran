@extends('errors.layout')

@section('kode', '500')
@section('judul', 'Terjadi Kesalahan')
@section('pesan')Maaf, ada gangguan di sistem kami. Coba lagi beberapa saat lagi. Bila masih terjadi, hubungi restoran di {{ config('resto.telepon') }}.@endsection
