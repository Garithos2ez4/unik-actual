<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EgresoFormatoExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([
            [
                '11/05/2026', 
                'Leonardo', 
                'DISCO SOLIDO OEM M2 256GB', 
                '1', 
                'Egreso', 
                'De Tienda', 
                'ML - Unik', 
                'UNK-SSDOEM256GB-100036', 
                '2000016365872746', 
                'SKU-EJEMPLO', 
                'FLEX', 
                ''
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Responsable',
            'Producto',
            'Un.',
            'Movimiento',
            'Almacén',
            'Plataforma',
            'SERIES',
            'Orden',
            'SKU',
            'Envío por',
            'Observaciones'
        ];
    }
}
