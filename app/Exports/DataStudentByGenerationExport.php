<?php

namespace App\Exports;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataStudentByGenerationExport implements
    FromCollection,
    WithMapping,
    WithHeadings,
    WithStyles,
    WithColumnWidths
{
    private $slug;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    public function collection()
    {
        $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();

        if ($roleStudent === null) {
            throw new \Exception('Không tìm thấy vai trò là học sinh');
        }

        $students = User::whereHas('roles', function ($query) use ($roleStudent) {
            $query->where('role_id', $roleStudent->id);
        })
            ->whereHas('generations', function ($query) {
                $query->where('slug', $this->slug);
            })
            ->get();

        return $students;
    }

    public function map($student): array
    {
        return [
            $student->name,
            $student->username,
            $student->email,
            $student->gender,
            $student->date_of_birth,
            $student->phone_number,
            $student->address,
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Username',
            'Email',
            'Gender',
            'Date Of Birth',
            'Phone Number',
            'Address',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 36,
            'D' => 10,
            'E' => 30,
            'F' => 20,
            'G' => 60,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A1:G1' => [
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
}
