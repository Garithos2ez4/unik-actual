<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;

class SyncFloresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:flores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las sucursales de FLORES desde el archivo JSON local hacia nuestra base de datos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización con FLORES...');

        // 1. Obtener la agencia FLORES local
        $agencia = Agencia::where('nombre', 'LIKE', '%FLORES%')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia FLORES en la base de datos.');
            return;
        }

        // 2. Leer archivo JSON
        $filePath = storage_path('app/flores_agencias.json');
        if (!file_exists($filePath)) {
            $this->error("No se encontró el archivo JSON en: {$filePath}");
            return;
        }

        $jsonStr = file_get_contents($filePath);
        $sucursales = json_decode($jsonStr, true);

        if (!is_array($sucursales)) {
            $this->error('El archivo flores_agencias.json no contiene un JSON válido.');
            return;
        }

        // --- BORRADO LÓGICO ---
        SubAgencia::where('idAgencia', $agencia->idAgencia)->update(['estado' => 0]);

        $nuevas = 0;
        $existentes_actualizadas = 0;
        $omitidas = 0;

        foreach ($sucursales as $item) {
            $codigo = trim($item['codigo_sunat'] ?? '');
            $tipo = trim($item['tipo_local'] ?? '');
            $direccion = trim($item['direccion'] ?? 'S/N');
            $distritoSunat = trim($item['distrito_sunat'] ?? '');

            // Omitir oficinas administrativas (ya filtrado en js, pero por si acaso)
            if (empty($codigo) || strtoupper($tipo) === 'OFICINA ADMINISTRATIVA') {
                continue;
            }

            // --- MEJORA 1: SOPORTE PARA TILDES Y EÑES ---
            // Usamos mb_strtoupper como en Olva para no perder distritos con caracteres especiales
            $distritoLimpio = mb_strtoupper($distritoSunat, 'UTF-8');

            // --- MEJORA 2: EXCEPCIONES DE FLORES / SUNAT ---
            $excepciones = [
                'JOSE LEONARDO ORTIZ' => 'JOSE LEONARDO ORTIZ',
                'NASCA' => 'NAZCA',
                // Añade aquí más distritos si ves que botan error al ejecutar
            ];

            if (array_key_exists($distritoLimpio, $excepciones)) {
                $distritoLimpio = $excepciones[$distritoLimpio];
            }

            // 3. Buscar el Destino localmente
            $destino = Destino::whereRaw('UPPER(nombre) = ?', [$distritoLimpio])->first();

            // La condición de LIMA ya no es necesaria con el filtro exacto, 
            // pero la dejamos como fallback de seguridad
            if (!$destino && $distritoLimpio === 'LIMA') {
                $destino = Destino::where('nombre', 'LIMA')->first();
            }

            if (!$destino) {
                $this->warn("Destino no encontrado para: {$distritoLimpio} (Agencia SUNAT Código: {$codigo})");
                $omitidas++;
                continue;
            }

            // --- MEJORA 3: INCLUIR EL TIPO DE LOCAL EN EL NOMBRE ---
            // Así el usuario (y tú) sabrán si es Agencia principal o Local
            $etiquetaTipo = '';
            if (str_contains(strtoupper($tipo), 'AG.')) {
                $etiquetaTipo = 'AGENCIA';
            } elseif (str_contains(strtoupper($tipo), 'LO.')) {
                $etiquetaTipo = 'LOCAL';
            } elseif (str_contains(strtoupper($tipo), 'DE.')) {
                $etiquetaTipo = 'DEPOSITO';
            }

            $nombreSucursal = "FLORES " . $destino->nombre;
            if ($etiquetaTipo !== '') {
                $nombreSucursal .= " (" . $etiquetaTipo . ")";
            }

            // --- MEJORA 4: DIFERENCIAR LOCALES EN EL MISMO DISTRITO ---
            // Flores puede tener 2 locales en el mismo distrito.
            // Si solo buscamos por nombre de sucursal, se van a chancar. 
            // Buscamos también por dirección para permitir múltiples sedes en un distrito.
            $subAgenciaExistente = SubAgencia::where('idAgencia', $agencia->idAgencia)
                ->where('idDestino', $destino->idDestino)
                ->where('direccion', substr($direccion, 0, 255))
                ->first();

            if (!$subAgenciaExistente) {
                SubAgencia::create([
                    'idAgencia' => $agencia->idAgencia,
                    'idDestino' => $destino->idDestino,
                    'nombre_oficina' => substr($nombreSucursal, 0, 50),
                    'direccion' => substr($direccion, 0, 255), // Limitar a 255 chars
                    'telefono' => '',
                    'estado' => 1
                ]);
                $nuevas++;
                $this->line("Creado: {$nombreSucursal} en Destino: {$destino->nombre}");
            } else {
                // Actualizamos la data rica
                $subAgenciaExistente->update([
                    'nombre_oficina' => substr($nombreSucursal, 0, 50),
                    'estado' => 1
                ]);
                $existentes_actualizadas++;
            }
        }

        $this->info("¡Sincronización Completada!");
        $this->info("- Nuevas Sub-Agencias: {$nuevas}");
        $this->info("- Actualizadas con Dirección: {$existentes_actualizadas}");
        $this->info("- Omitidas (Sin destino): {$omitidas}");
    }
}
