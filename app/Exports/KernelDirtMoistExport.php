<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class KernelDirtMoistExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $masterData;
    protected $startDate;
    protected $endDate;
    protected $kode;

    public function __construct($data, $masterData, string $startDate, string $endDate, ?string $kode = null)
    {
        $this->data = $data;
        $this->masterData = $masterData;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->kode = $kode;
    }

    public function collection(): Collection
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'NO',
            'BULAN',
            'TANGGAL',
            'JAM',
            'KODE',
            'NAMA SAMPLE',
            'INPUTED BY',
            'JENIS',
            'OPERATOR',
            'SAMPEL BOY',
            'PENGULANGAN',
            'KEGIATAN DISPATCH',
            'REMARKS',
            'BERAT SAMPEL (g)',
            'BERAT DIRTY (g)',
            'DIRTY TO SAMPEL (%)',
            'LIMIT DIRTY',
            'KADAR AIR KERNEL (%)',
            'LIMIT MOIST',
        ];
    }

    public function map($row): array
    {
        static $counter = 0;
        $counter++;

        $master = $this->masterData[$row->kode] ?? null;
        $metricKey = str_starts_with(strtoupper($row->kode ?? ''), 'OUT') ? 'moist' : 'dirty';

        $dirtyVal = $metricKey === 'dirty' ? (float) ($row->dirty_to_sampel ?? 0) : null;
        $moistVal = $metricKey === 'moist' ? (float) ($row->moist_percent ?? 0) : null;
        $dirtyLimOp = $metricKey === 'dirty' ? ($row->dirty_limit_operator ?? null) : null;
        $dirtyLimV = $metricKey === 'dirty' && $row->dirty_limit_value !== null ? (float) $row->dirty_limit_value : null;
        $moistLimOp = $metricKey === 'moist' ? ($row->moist_limit_operator ?? null) : null;
        $moistLimV = $metricKey === 'moist' && $row->moist_limit_value !== null ? (float) $row->moist_limit_value : null;

        $dirtySymbol = match ($dirtyLimOp) {
            'lt' => '< ',
            'ge' => '>= ',
            'gt' => '> ',
            default => '<= ',
        };
        $moistSymbol = match ($moistLimOp) {
            'lt' => '< ',
            'ge' => '>= ',
            'gt' => '> ',
            default => '<= ',
        };

        $dirtyLimit = $dirtyLimV !== null ? $dirtySymbol . number_format($dirtyLimV, 4) . '%' : '-';
        $moistLimit = $moistLimV !== null ? $moistSymbol . number_format($moistLimV, 4) . '%' : '-';

        return [
            $counter,
            Carbon::parse($row->created_at)->format('F Y'),
            Carbon::parse($row->created_at)->format('d-m-Y'),
            Carbon::parse($row->created_at)->format('H:i:s'),
            $row->kode ?? '-',
            $master ? $master->nama_sample : '-',
            data_get($row, 'user.name', '-'),
            $row->jenis ?? '-',
            $row->operator ?? '-',
            $row->sampel_boy ?? '-',
            ($row->pengulangan ?? false) ? 'Ya' : 'Tidak',
            ($row->kegiatan_dispek ?? false) ? 'Ya' : 'Tidak',
            $row->remarks ?? '-',
            (float) ($row->berat_sampel ?? 0),
            (float) ($row->berat_dirty ?? 0),
            $dirtyVal !== null ? round($dirtyVal, 4) : '-',
            $dirtyLimit,
            $moistVal !== null ? round($moistVal, 4) : '-',
            $moistLimit,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->insertNewRowBefore(1, 3);

        $sheet->setCellValue('A1', 'LAPORAN DATA DIRT & MOIST');
        $sheet->mergeCells('A1:S1');

        $filterInfo = 'Periode: ' . Carbon::parse($this->startDate)->format('d-m-Y')
            . ' s/d ' . Carbon::parse($this->endDate)->format('d-m-Y');
        if ($this->kode) {
            $filterInfo .= ' | Kode: ' . $this->kode;
        }
        $sheet->setCellValue('A2', $filterInfo);
        $sheet->mergeCells('A2:S2');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A4:S4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getStyle('A4:S' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
        $sheet->getStyle('N5:S' . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // Conditional colour for DIRTY column (P) and MOIST column (R)
        for ($r = 5; $r <= $lastRow; $r++) {
            $dirty = $sheet->getCell('P' . $r)->getValue();
            $dirtyLim = $sheet->getCell('Q' . $r)->getValue();
            if (is_numeric($dirty) && $dirtyLim !== '-') {
                preg_match('/([<>]=?)\s*([\d.]+)/', (string) $dirtyLim, $m);
                if (count($m) === 3) {
                    $op = trim($m[1]);
                    $lim = (float) $m[2];
                    $ok = ($op === '<=' || $op === '=<') ? $dirty <= $lim : $dirty > $lim;
                    $sheet->getStyle('P' . $r)->applyFromArray($ok ? [
                        'font' => ['color' => ['rgb' => '16a34a'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dcfce7']],
                    ] : [
                        'font' => ['color' => ['rgb' => 'dc2626'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'fee2e2']],
                    ]);
                }
            }

            $moist = $sheet->getCell('R' . $r)->getValue();
            $moistLim = $sheet->getCell('S' . $r)->getValue();
            if (is_numeric($moist) && $moistLim !== '-') {
                preg_match('/([<>]=?)\s*([\d.]+)/', (string) $moistLim, $m);
                if (count($m) === 3) {
                    $op = trim($m[1]);
                    $lim = (float) $m[2];
                    $ok = ($op === '<=' || $op === '=<') ? $moist <= $lim : $moist > $lim;
                    $sheet->getStyle('R' . $r)->applyFromArray($ok ? [
                        'font' => ['color' => ['rgb' => '16a34a'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dcfce7']],
                    ] : [
                        'font' => ['color' => ['rgb' => 'dc2626'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'fee2e2']],
                    ]);
                }
            }
        }

        return [];
    }
}
