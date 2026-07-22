<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToCollection;

class LicenciaImport implements ToCollection, WithHeadingRow
{
    public Collection $rows;

    public function __construct()
    {
        // Inicializar como colección vacía para evitar errores si el Excel viene vacío
        $this->rows = collect();
    }

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }
}
