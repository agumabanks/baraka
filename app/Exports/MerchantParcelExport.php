<?php

namespace App\Exports;

use App\Http\Resources\MerchantParcelExportResource;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MerchantParcelExport implements FromCollection, WithEvents, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $merchantParcel;

    public function __construct($merchantParcel)
    {
        $this->merchantParcel = $merchantParcel;
    }

    public function headings(): array
    {
        return [
            'Invoice ID',
            'Tracking ID',
            'Customer Name',
            'Customer Phone',
            'Customer Address',
            'Status',
            'Cash Collection',
            'Delivery Charge',
            'Vat',
            'COD',
            'Total Charge',
            'Payable',
        ];
    }

    public function collection()
    {
        return MerchantParcelExportResource::collection($this->merchantParcel);

    }

    public function registerEvents(): array
    {

        return [
            // Array callable, refering to a static method.
            AfterSheet::class => function (AfterSheet $event) {
                $total_rows = $event->sheet->getHighestRow();
                $last_row = $total_rows + 1;
                $event->sheet->setCellValue('F'.$last_row, 'Total=');
                $event->sheet->setCellValue('G'.$last_row, '=SUM(G2:G'.$total_rows.')'); // total cash collection
                $event->sheet->setCellValue('H'.$last_row, '=SUM(H2:H'.$total_rows.')'); // total delivery charges
                $event->sheet->setCellValue('I'.$last_row, '=SUM(I2:I'.$total_rows.')'); // total vat
                $event->sheet->setCellValue('J'.$last_row, '=SUM(J2:J'.$total_rows.')'); // total cod
                $event->sheet->setCellValue('K'.$last_row, '=SUM(K2:K'.$total_rows.')'); // total charges
                $event->sheet->setCellValue('L'.$last_row, '=SUM(L2:L'.$total_rows.')'); // total payable

                $event->sheet->getStyle($last_row)->applyFromArray([
                    'font' => ['bold' => true],
                ]);

            },
        ];
    }

    public function styles(Worksheet $sheet)
    {

        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }
}
