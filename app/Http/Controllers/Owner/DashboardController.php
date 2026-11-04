<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LaporanService $laporan): View
    {
        $periode = array_key_exists($request->query('periode'), LaporanService::PERIODE) ? $request->query('periode') : 'harian';

        return view('owner.dashboard', $laporan->dashboard($periode));
    }
}
