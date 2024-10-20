<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentFormExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnWidths, WithColumnFormatting, WithEvents
{
    public function collection()
    {
        return collect(
            []
        );
    }

    public function headings(): array
    {
        return [
            'Full Name',
            'Date Of Birth',
            'Gender',
            'Address',
            'Phone Number',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 25,
            'C' => 10,
            'D' => 40,
            'E' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A1:E1' => [
                'font' => [
                    'name' => 'Arial',
                    'bold' => false,
                    'italic' => false,
                    'size' => 13,
                    'color' => ['argb' => '000000'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'DCDCDC',
                    ],
                ],

            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $dobValidation = $sheet->getCell('B2')->getDataValidation();
                $dobValidation->setType(DataValidation::TYPE_DATE);
                $dobValidation->setErrorStyle(DataValidation::STYLE_WARNING);
                $dobValidation->setAllowBlank(false);
                $dobValidation->setShowErrorMessage(true);
                $dobValidation->setPromptTitle('Chọn ngày sinh');
                $dobValidation->setPrompt('Vui lòng chọn một ngày hợp lệ.');
                $dobValidation->setFormula1('DATE(1900, 1, 1)');
                $dobValidation->setFormula2('DATE(2100, 12, 31)');
                $dobValidation->setErrorTitle('Lỗi nhập liệu');
                $dobValidation->setError('Ngày sinh không hợp lệ. Vui lòng chọn một ngày trong khoảng từ 01/01/1900 đến 31/12/2100.');
                $sheet->setDataValidation('B2:B1048576', $dobValidation);

                $genderValidation = $sheet->getCell('C2')->getDataValidation();
                $genderValidation->setType(DataValidation::TYPE_LIST);
                $genderValidation->setErrorStyle(DataValidation::STYLE_WARNING);
                $genderValidation->setFormula1('"Male,Female"');
                $genderValidation->setAllowBlank(false);
                $genderValidation->setShowErrorMessage(true);
                $genderValidation->setShowDropDown(true);
                $genderValidation->setErrorTitle('Nhập lỗi');
                $genderValidation->setError('Giá trị không tồn tại trong danh sách');
                $genderValidation->setPromptTitle('Chọn 1 giá trị trong danh sách');
                $genderValidation->setPrompt('Vui lòng hãy chọn 1 giá trị trong danh sách');

                $sheet->setDataValidation('C2:C1048576', $genderValidation);
            },
        ];
    }
}
