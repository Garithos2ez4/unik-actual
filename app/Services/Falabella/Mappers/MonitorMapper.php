<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Catalogo\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

class MonitorMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '483 - Electrónica / Computación / Periféricos de Monitor';

    public function getTemplateFile(): string
    {
        return 'falabella/falabella_template_express_base.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'ProductCreationTemplate-Express_';
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
        $caract   = $d['caractMap'];

        // Traemos la descripción por si hay detalles técnicos ocultos ahí
        $desc     = $producto->descripcionProducto ?? '';

        // 1. Mapeo de características (Básicas y Nuevas de tu panel)
        $resol     = $d['resolFbk'] ?: ($caract['Resolucion'] ?? '');
        $hz        = $caract['Hz'] ?? $d['hzFbk'] ?? '';
        $panel     = $caract['Panel'] ?? '';
        $pulgadas  = $caract['Pulgadas'] ?? '';
        $respuesta = $caract['Tiempo de respuesta'] ?? '';

        // Atributos de alto valor SEO para Monitores
        $curvoVal  = $caract['Curvo'] ?? '';
        $montaje   = $caract['Montaje de pared'] ?? '';
        $brillo    = $caract['Brillo'] ?? '';
        $puertos   = $caract['Puertos'] ?? '';

        // Metemos textos descriptivos a la piscina (excluimos 'Curvo' para evaluarlo aparte)
        $textoCompleto = "$base $desc $montaje $puertos $brillo";

        // 2. Limpieza y Extracción de variables

        // Pantalla (Estandarizamos las comillas)
        $pantalla = $pulgadas ? str_replace("'", "", $pulgadas) . '"' : '';

        // Resolución Corta (FHD, QHD, 4K)
        $resolCorta = '';
        if ($resol) {
            if (preg_match('/(FHD|QHD|UHD|4K|2K|1080p|1440p)/i', $resol, $m)) {
                $resolCorta = strtoupper($m[1]);
            } else {
                $resolCorta = explode(' ', $resol)[0];
            }
        }

        // Velocidad (limpiamos espacios, ej: "1 MS" a "1ms")
        $msCorto = $respuesta ? str_replace(' ', '', strtolower($respuesta)) : '';
        $hzCorto = $hz ? str_replace(' ', '', strtolower($hz)) : '';

        // ¿Es Curvo? (Solo si el campo dice explícitamente "Si" o "Sí", o el nombre base ya lo incluye)
        $isCurvo = '';
        if (preg_match('/^(si|sí)$/i', trim($curvoVal)) || preg_match('/\b(curvo|curved)\b/i', $base)) {
            $isCurvo = 'Curvo';
        }

        // ¿Tiene montaje VESA? (Súper buscado para soportes)
        $hasVesa = preg_match('/\b(vesa)\b/i', $textoCompleto) ? 'VESA' : '';

        // Detección de sincronización (FreeSync / G-Sync)
        $freesync  = stripos($textoCompleto . ($caract['FreeSync'] ?? '') . ($caract['Tecnologia'] ?? ''), 'freesync') !== false ? 'FreeSync' : '';
        $gsync     = stripos($textoCompleto . ($caract['GSync'] ?? '') . ($caract['Tecnologia'] ?? ''), 'g-sync') !== false ? 'G-Sync' : '';
        $syncTech  = array_filter([$freesync, $gsync]);
        $syncStr   = count($syncTech) > 0 ? implode('/', $syncTech) : '';


        // 3. Construcción de Títulos SEO

        $titulo1 = $base;

        // Título 2: Enfoque Gamer / Fluidez (Ej: Monitor Gamer Curvo Teros 2417S 23.8" 144hz 1ms IPS FreeSync)
        $t2_parts = array_filter(['Monitor Gamer', $isCurvo, $marca, $modelo, $pantalla, $hzCorto, $msCorto, $panel, $syncStr]);
        $titulo2 = implode(' ', $t2_parts);

        // Título 3: Enfoque Técnico / Productividad (Ej: Monitor Teros 2417S 23.8" FHD IPS VESA)
        $t3_parts = array_filter(['Monitor', $isCurvo, $marca, $modelo, $pantalla, $resolCorta, $panel, $hasVesa]);
        $titulo3 = implode(' ', $t3_parts);

        // Fallback: Si no hay modelo registrado en la BD
        if (empty($modelo)) {
            $baseCorta = implode(' ', array_slice(explode(' ', $base), 0, 3));
            $titulo2 = trim($baseCorta . ' Gamer ' . implode(' ', array_filter([$isCurvo, $pantalla, $hzCorto, $msCorto])));
            $titulo3 = trim(str_replace('GAMER', '', strtoupper($baseCorta)) . ' ' . implode(' ', array_filter([$isCurvo, $pantalla, $resolCorta, $panel, $hasVesa])));
        }

        // Retornamos aplicando el filtro estricto de Falabella (Mínimo 20 caracteres para Monitores)
        return [
            'titulo1' => $this->ajustarLongitudFalabella($titulo1, 20),
            'titulo2' => $this->ajustarLongitudFalabella($titulo2, 20),
            'titulo3' => $this->ajustarLongitudFalabella($titulo3, 20),
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
    private function ajustarLongitudFalabella(string $titulo, int $minimo = 30): string
    {
        // 1. Limpiar espacios dobles
        $titulo = trim(preg_replace('/\s+/', ' ', $titulo));

        // 2. Si supera los 100 caracteres, recortar sin romper palabras
        if (strlen($titulo) > 100) {
            $titulo = substr($titulo, 0, 100);
            $ultimoEspacio = strrpos($titulo, ' ');
            if ($ultimoEspacio !== false) {
                $titulo = substr($titulo, 0, $ultimoEspacio);
            }
        }

        // 3. Si tiene menos del mínimo exigido (ej. 30), agregar relleno comercial
        if (strlen($titulo) < $minimo) {
            $titulo .= ' Original Nuevo Garantizado';

            // Por si acaso el relleno nos hace pasar de 100
            if (strlen($titulo) > 100) {
                $titulo = substr($titulo, 0, 100);
            }
        }

        return trim($titulo);
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
            'N' => $d['resolFbk'],
            'O' => $d['pulgadasCm'] ?: (preg_match('/(\d+[\.,]?\d*)\s*(?:"|pulgadas|inch)/i', $var['titulo'], $m) ? str_replace(',', '.', $m[1]) : '24'),
            'P' => '1',
            'Q' => $d['caractMap']['Panel'] ?? '',
            'S' => $d['dim'],
            'U' => 'Nuevo',
            'V' => max(5, (float)$d['anchoCm']),
            'W' => max(5, (float)$d['largoCm']),
            'X' => max(5, (float)$d['altoCm']),
            'Y' => $d['pesoCm'],
        ];
    }
}
