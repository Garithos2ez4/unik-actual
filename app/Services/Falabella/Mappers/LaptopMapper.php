<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

class LaptopMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '432 - Electrónica / Computación / Computadores / Portátiles|notebooks';

    public function getTemplateFile(): string
    {
        return 'falabella/laptopTemplate-Express_2026-07-14.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'LaptopTemplate-Express_';
    }

    public function getStartRow(): int
    {
        return 5;
    }

    public function sugerirTitulos(Producto $producto, array $d): array
    {
        $base       = $producto->nombreProducto;
        $marca      = $d['marca'] ?? 'HP';
        $modelo     = $producto->modelo ?? '';
        $caract     = $d['caractMap'];

        // 1. Mapeo de características (Asegurando coincidencia con tus campos)
        $procesador = $caract['Procesador'] ?? '';
        $ram        = $caract['Memoria RAM'] ?? $caract['RAM'] ?? '';
        $storage    = $caract['Almacenamiento'] ?? $d['almacenamientoFbk'] ?? '';
        $pulgadas   = $caract['Pulgadas'] ?? '';
        $gpu        = $caract['Graficos'] ?? $caract['Tarjeta de Video'] ?? '';
        $hz         = $caract['Hz'] ?? $d['hzFbk'] ?? '';

        // 2. Limpieza de variables para SEO (Textos cortos y directos)

        // Extraer procesador corto (Ej: "Core i7" o "Ryzen 7")
        $procCorto = '';
        if ($procesador) {
            if (preg_match('/(Ryzen\s+\d+|Core\s+i\d+|Core\s+Ultra|M\d+|Snapdragon)/i', $procesador, $m)) {
                $procCorto = $m[1];
            } else {
                $procCorto = substr($procesador, 0, 15);
            }
        }

        // Limpiar GPU (Ej: extraer solo "RTX 4070")
        $gpuCorta = '';
        if ($gpu) {
            if (preg_match('/(RTX\s*\d+|GTX\s*\d+|Radeon\s*(RX)?\s*\d+|Arc\s*[A-Z]\d+)/i', $gpu, $m)) {
                $gpuCorta = strtoupper($m[0]);
            } else {
                $gpuCorta = substr($gpu, 0, 15);
            }
        }

        // Limpiar formato de pulgadas (Quitar la comilla simple si existe y poner comilla doble)
        $pantalla = $pulgadas ? str_replace("'", "", $pulgadas) . '"' : '';

        // 3. Construcción de Títulos SEO

        // Título 1: El del sistema (Intacto)
        $titulo1 = $base;

        // Título 2: Enfoque Poder / Gamer
        $t2_parts = array_filter(['Laptop Gamer', $marca, $modelo, $procCorto, $ram, $gpuCorta]);
        $titulo2 = implode(' ', $t2_parts);

        // Título 3: Enfoque Técnico / Pantalla (Usamos 'Notebook' para captar otra intención de búsqueda)
        $t3_parts = array_filter(['Notebook', $marca, $modelo, $pantalla, $hz, $storage, $procCorto]);
        $titulo3 = implode(' ', $t3_parts);

        // Fallback: Si el producto no tiene modelo registrado en BD, armamos algo decente con el nombre original
        if (empty($modelo)) {
            // Tomamos solo las primeras 3 palabras del título original (Ej: LAPTOP HP VICTUS)
            $baseCorta = implode(' ', array_slice(explode(' ', $base), 0, 3));

            $titulo2 = $baseCorta . ' Gamer ' . implode(' ', array_filter([$procCorto, $ram, $gpuCorta]));
            $titulo3 = str_replace('LAPTOP', 'Notebook', $baseCorta) . ' ' . implode(' ', array_filter([$pantalla, $hz, $storage, $procCorto]));
        }

        return [
            'titulo1' => $titulo1,
            'titulo2' => trim($titulo2),
            'titulo3' => trim($titulo3),
        ];
    }
    public function getVariaciones(Producto $producto, callable $buildSku, ?object $user, array $titulos = []): array
    {
        $t1 = $titulos['titulo1'] ?? $producto->nombreProducto;
        $t2 = $titulos['titulo2'] ?? ($producto->nombreProducto . ' Gamer');
        $t3 = $titulos['titulo3'] ?? ($producto->nombreProducto . ' Oficina');

        return [
            ['titulo' => $t1, 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 1), 'variacion' => 1],
            ['titulo' => $t2, 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 2), 'variacion' => 2],
            ['titulo' => $t3, 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 3), 'variacion' => 3],
        ];
    }

    public function buildColumnData(array $var, array $d, Producto $producto, array $context): array
    {
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
            'N' => $this->resolveTipoComputador($var['titulo'], $d['marca']),
            'O' => $d['nucleosFbk'],
            'P' => $d['pulgadasCm'],
            'Q' => $d['caractMap']['Procesador'] ?? '',
            'R' => $this->resolveSistemaOperativo($d['caractMap']['Sistema Operativo'] ?? ''),
            'S' => $d['almacenamientoFbk'],
            'T' => $d['resolFbk'],
            'U' => 'Nuevo',
            'V' => max(5, (float)$d['anchoCm']),
            'W' => max(5, (float)$d['largoCm']),
            'X' => max(5, (float)$d['altoCm']),
            'Y' => $d['pesoCm'],
        ];
    }

    private function resolveTipoComputador(string $titulo, string $marca): string
    {
        $tituloLower = strtolower($titulo);
        $marcaLower  = strtolower($marca);

        if (stripos($tituloLower, 'macbook') !== false || stripos($marcaLower, 'apple') !== false) {
            return 'Macbook';
        }
        if (stripos($tituloLower, 'gamer') !== false) {
            return 'Laptop Gamer';
        }
        if (preg_match('/2\s*en\s*1|2-en-1/i', $tituloLower)) {
            return '2 en 1';
        }
        return 'Laptop';
    }

    private function resolveSistemaOperativo(string $os): string
    {
        if (!$os) return '';
        $osLower = strtolower($os);

        if (stripos($osLower, 'windows 11') !== false || stripos($osLower, 'win 11') !== false) {
            return 'Windows 11';
        }
        if (stripos($osLower, 'windows 10') !== false || stripos($osLower, 'win 10') !== false) {
            return 'Windows 10';
        }
        if (stripos($osLower, 'mac') !== false) {
            return 'Mac OS'; // Ajustar si en el Excel es distinto
        }
        if (stripos($osLower, 'chrome') !== false) {
            return 'Chrome OS';
        }
        if (stripos($osLower, 'linux') !== false || stripos($osLower, 'ubuntu') !== false) {
            return 'Linux';
        }
        if (stripos($osLower, 'free') !== false || stripos($osLower, 'dos') !== false || stripos($osLower, 'no os') !== false) {
            return 'Free DOS';
        }

        return $os;
    }
}
