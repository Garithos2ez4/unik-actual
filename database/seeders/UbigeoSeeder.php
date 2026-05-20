<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbigeoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Limpiando tablas Wey");

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('destinos')->truncate();
        DB::table('provincias')->truncate();
        DB::table('departamentos')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $filePath = database_path("seeders/ubigeo_distrito.csv");

        if (!file_exists($filePath)) {
            $this->command->error("Error: El archivo no se encuentra.");
            return;
        }

        $csvFile = fopen($filePath, "r");

        $headers = fgetcsv($csvFile, 0, ",");
        $headers = array_map('trim', $headers);
        $columns = array_flip($headers);

        $idxDepto = $columns['departamento'];
        $idxProv = $columns['provincia'];
        $idxDist = $columns['distrito'];

        $rawRows = [];
        $uniqueDeptos = [];
        $uniqueProvs = [];

        while (($data = fgetcsv($csvFile, 0, ",")) !== FALSE) {
            if (empty($data[$idxDepto]) || empty($data[$idxProv]) || empty($data[$idxDist])) {
                continue;
            }

            $deptoNombre = trim($data[$idxDepto]);
            $provNombre = trim($data[$idxProv]);
            $distNombre = trim($data[$idxDist]);

            $uniqueDeptos[$deptoNombre] = true;

            // Usamos una llave combinada "Depto|Prov" por si existen 2 provincias con el mismo nombre en distintos departamentos
            $uniqueProvs[$deptoNombre . '|' . $provNombre] = [
                'departamento' => $deptoNombre,
                'provincia' => $provNombre
            ];

            $rawRows[] = [
                'depto' => $deptoNombre,
                'prov'  => $provNombre,
                'dist'  => $distNombre
            ];
        }
        fclose($csvFile);

        $this->command->info("1. Insertando Departamentos...");
        $mapDeptos = [];
        foreach (array_keys($uniqueDeptos) as $depto) {
            $mapDeptos[$depto] = DB::table('departamentos')->insertGetId([
                'nombre' => $depto,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("2. Insertando Provincias enlazadas...");
        $mapProvs = [];
        foreach ($uniqueProvs as $key => $val) {
            $mapProvs[$key] = DB::table('provincias')->insertGetId([
                'idDepartamento' => $mapDeptos[$val['departamento']], // Conectamos con su depto
                'nombre' => $val['provincia'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("3. Insertando Destinos masivamente...");
        $destinosMasivos = [];
        foreach ($rawRows as $row) {
            $keyProv = $row['depto'] . '|' . $row['prov'];
            $destinosMasivos[] = [
                'idProvincia' => $mapProvs[$keyProv], // Conectamos con su provincia
                'nombre'      => $row['dist'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        foreach (array_chunk($destinosMasivos, 500) as $chunk) {
            DB::table('destinos')->insert($chunk);
        }

        $this->command->info("¡Éxito! Registrados: " . count($mapDeptos) . " departamentos, " . count($mapProvs) . " provincias y " . count($destinosMasivos) . " destinos.");
    }
}
