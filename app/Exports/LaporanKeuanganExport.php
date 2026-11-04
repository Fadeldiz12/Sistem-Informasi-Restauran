<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanKeuanganExport implements FromView, ShouldAutoSize, WithColumnFormatting, WithTitle
{
    public function __construct(private array $data) {}

    public function view(): View
    {
        return view('owner.laporan-excel', $this->data);
    }

    public function columnFormats(): array
    {
        $rp = '"Rp" #,##0;-"Rp" #,##0';

        return ['B' => $rp, 'C' => $rp, 'D' => $rp];
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }
}
