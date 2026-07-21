<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

class CableMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '44 - Electrónica / Telefonía / Dispositivos|servicios de comunicación móvil / Accesorios para teléfonos móviles|smartphone';

    public function getTemplateFile(): string
    {
        return 'falabella/CablesTemplate-Express_2026-07-21_132548.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'CablesTemplate-Express_';
    }

    public function getStartRow(): int
    {
        return 5;
    }

    public function sugerirTitulos(Producto $producto, array $d): array
    {
        $base     = $producto->nombreProducto;
        $marca    = $d['marca'] ?? '';
        $modelo   = $producto->modelo ?? '';

        // Título 1: Original
        $titulo1 = $base;
        if (!preg_match('/' . preg_quote($marca, '/') . '/i', $titulo1) && !empty($marca)) {
            $titulo1 = $marca . ' ' . $titulo1;
        }

        // Título 2: Con modelo
        $titulo2 = $titulo1;
        if (!empty($modelo) && stripos($titulo2, $modelo) === false) {
            $titulo2 .= ' ' . $modelo;
        }

        // Título 3: Alta Velocidad
        $titulo3 = $titulo1 . ' Alta Velocidad';

        return [
            'titulo1' => $this->ajustarLongitudFalabella($titulo1),
            'titulo2' => $this->ajustarLongitudFalabella($titulo2),
            'titulo3' => $this->ajustarLongitudFalabella($titulo3),
        ];
    }

    public function getVariaciones(Producto $producto, callable $buildSku, ?object $user, array $titulos = []): array
    {
        $variaciones = [];

        $t1 = !empty($titulos['titulo1']) ? $titulos['titulo1'] : $producto->nombreProducto;
        $variaciones[] = ['titulo' => $t1, 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 1), 'variacion' => 1];

        if (!empty($titulos['titulo2'])) {
            $variaciones[] = ['titulo' => $titulos['titulo2'], 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 2), 'variacion' => 2];
        }

        if (!empty($titulos['titulo3'])) {
            $variaciones[] = ['titulo' => $titulos['titulo3'], 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 3), 'variacion' => 3];
        }

        return $variaciones;
    }

    public function buildColumnData(array $var, array $d, Producto $producto, array $context): array
    {
        $conectividadInput = $d['caractMap']['Conectividad'] ?? $d['caractMap']['Tipo de cable'] ?? $var['titulo'];
        $conectividad = $this->resolveConectividad($conectividadInput);

        return [
            'A' => $var['titulo'],
            'B' => $d['marca'],
            'C' => $producto->descripcionProducto,
            'D' => self::CATEGORIA,
            'E' => $var['sku'],
            'F' => substr($d['upcFbk'], 0, 13) . ($var['variacion'] ?? 1),
            'G' => '...',
            'H' => $context['stockTotal'],
            'I' => $d['precioNormalFmt'],
            'J' => $d['precioFmt'],
            'K' => $context['saleStart'],
            'L' => $context['saleEnd'],
            'M' => '2026',
            'N' => $conectividad,
            'O' => $d['caractMap']['Tipo de accesorio'] ?? 'Cable',
            'P' => $d['dim'],
            'Q' => $d['caractMap']['Material'] ?? '',
            'R' => 'Nuevo',
            'S' => max(5, (float)$d['anchoCm']),
            'T' => max(5, (float)$d['largoCm']),
            'U' => max(5, (float)$d['altoCm']),
            'V' => $d['pesoCm'],
        ];
    }

    private function resolveConectividad(string $texto): string
    {
        $opciones = [
            '2G',
            '3G',
            '4G',
            '5G',
            'Alámbrico',
            'Analógico UHF',
            'Android auto',
            'Auxiliar 3.5mm',
            'Bluetooth',
            'Cableado',
            'Coaxial Digital',
            'DVI',
            'Ethernet',
            'HDMI',
            'Inalámbrico',
            'Infrarrojo',
            'Jack 6.35mm',
            'Micro USB',
            'NFC',
            'No aplica',
            'Óptico (Toslink)',
            'Otro',
            'Para auto',
            'Pared',
            'Pared/auto',
            'Portable',
            'PS/2',
            'Radiofrecuencia (RF)',
            'RCA',
            'RJ-11',
            'Solar',
            'TDT',
            'Thunderbolt',
            'UHF digital',
            'USB-C',
            'USB',
            'VGA',
            'WF wireless',
            'Wifi 6',
            'Wifi',
            'XLR'
        ];

        // Normalizaciones comunes
        if (stripos($texto, 'tipo c') !== false || stripos($texto, 'type c') !== false || stripos($texto, 'usb c') !== false) {
            return 'USB-C';
        }
        if (stripos($texto, 'rj45') !== false || stripos($texto, 'red') !== false || stripos($texto, 'lan') !== false) {
            return 'Ethernet';
        }
        if (stripos($texto, 'v8') !== false || stripos($texto, 'micro-usb') !== false) {
            return 'Micro USB';
        }
        if (stripos($texto, 'audio') !== false || stripos($texto, 'jack') !== false || stripos($texto, '3.5') !== false) {
            return 'Auxiliar 3.5mm';
        }
        if (stripos($texto, 'plug') !== false || stripos($texto, '6.3') !== false) {
            return 'Jack 6.35mm';
        }
        if (stripos($texto, 'inalambrico') !== false || stripos($texto, 'wireless') !== false) {
            return 'Inalámbrico';
        }

        foreach ($opciones as $opcion) {
            if (stripos($texto, $opcion) !== false) {
                return $opcion;
            }
        }

        return 'Cableado';
    }

    private function ajustarLongitudFalabella(string $titulo, int $minimo = 30): string
    {
        if (strlen($titulo) < $minimo) {
            $titulo .= ' ' . trim(str_repeat('Modelo ', ceil(($minimo - strlen($titulo)) / 7)));
        }
        return trim($titulo);
    }
}
