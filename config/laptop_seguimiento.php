<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proveedores de Importación / Seguimiento de Laptops (Columnas M - P)
    |--------------------------------------------------------------------------
    | Mapea las columnas del archivo Libro11.xlsx con los registros reales
    | de la tabla `Preveedor`.
    | - CUAD -> idProveedor 40 (CUADRADO ELECTRONICS)
    | - DM   -> idProveedor 59 (DM COMPUTO)
    | - ALC  -> idProveedor 48 (alcosto.pe)
    | - ASIA -> idProveedor 22 (ASIA - BEST LAPTOP S.A.C., NO Asia Trade 47)
    */
    'proveedores' => [
        40 => [
            'idProveedor' => 40,
            'codigo'      => 'CUAD',
            'nombre'      => 'CUADRADO ELECTRONICS',
            'col_excel'   => 'M',
        ],
        59 => [
            'idProveedor' => 59,
            'codigo'      => 'DM',
            'nombre'      => 'DM COMPUTO',
            'col_excel'   => 'N',
        ],
        48 => [
            'idProveedor' => 48,
            'codigo'      => 'ALC',
            'nombre'      => 'alcosto.pe',
            'col_excel'   => 'O',
        ],
        22 => [
            'idProveedor' => 22,
            'codigo'      => 'ASIA',
            'nombre'      => 'ASIA (BEST LAPTOP)',
            'col_excel'   => 'P',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuentas de Plataforma (Columna R - CUENTA ML)
    |--------------------------------------------------------------------------
    | Mapea las siglas de la columna R del Excel con `CuentasPlataforma` y
    | con los seller_id oficiales de `MercadoLibreCredential`:
    | - UNIK   -> idCuentaPlataforma 3 (Unik Store - seller_id 1019333417)
    | - GAMING -> idCuentaPlataforma 4 (Gaming Power / Liz - seller_id 311984758)
    | - UP     -> idCuentaPlataforma 5 (Ultra Print - seller_id 1840857876)
    */
    'cuentas_ml' => [
        3 => [
            'idCuentaPlataforma' => 3,
            'sigla'              => 'UNIK',
            'nombre'             => 'Unik Store (ML)',
            'color'              => 'primary',
            'seller_id'          => '1019333417',
            'aliases'            => ['UNIK', 'ML UNIK'],
        ],
        4 => [
            'idCuentaPlataforma' => 4,
            'sigla'              => 'GAMING',
            'nombre'             => 'Gaming Power (ML)',
            'color'              => 'dark',
            'seller_id'          => '311984758',
            'aliases'            => ['GAMING', 'WEB GAMING', 'LIZ', 'GAMINGPOWERPERU', 'GAMING POWERPERU', 'GAMING POWER'],
        ],
        5 => [
            'idCuentaPlataforma' => 5,
            'sigla'              => 'UP',
            'nombre'             => 'Ultra Print (ML)',
            'color'              => 'info',
            'seller_id'          => '1840857876',
            'aliases'            => ['UP', 'ULTRA PRINT'],
        ],
    ],

    /*
    | Habilitar sincronización con la API de Mercado Libre al encender/apagar cuentas.
    | Si la app de ML tiene permisos de escritura (write), pausará o reactivará el ítem.
    */
    'ml_sync_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Marcas de Laptops y Colores de Identificación Visual
    |--------------------------------------------------------------------------
    | En Libro11.xlsx: Gris = productos ACER (#BFBFBF), Salmón = productos ASUS (#F6C6AD)
    */
    'marcas' => [
        'ACER' => [
            'idMarca'    => 5,
            'color_fila' => '#EFEFEF',
            'color_hdr'  => '#0070C0',
        ],
        'ASUS' => [
            'idMarca'    => 4,
            'color_fila' => '#FCE8DC',
            'color_hdr'  => '#0070C0',
        ],
        'DELL' => [
            'idMarca'    => 26,
            'color_fila' => '#F8F9FA',
            'color_hdr'  => '#0070C0',
        ],
        'GIGABYTE' => [
            'idMarca'    => 39,
            'color_fila' => '#F8F9FA',
            'color_hdr'  => '#0070C0',
        ],
        'HP' => [
            'idMarca'    => 18,
            'color_fila' => '#F8F9FA',
            'color_hdr'  => '#0070C0',
        ],
        'LENOVO' => [
            'idMarca'    => 24,
            'color_fila' => '#F8F9FA',
            'color_hdr'  => '#0070C0',
        ],
        'MSI' => [
            'idMarca'    => 3,
            'color_fila' => '#F8F9FA',
            'color_hdr'  => '#0070C0',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados de Disponibilidad (Leyenda fila 1 y columna S del Excel)
    |--------------------------------------------------------------------------
    */
    'estados' => [
        'CON_STOCK' => [
            'label' => 'Hay Stock (Proveedor o Tienda)',
            'short' => 'Con Stock',
            'bg'    => '#66FF33',
            'text'  => '#000000',
            'badge' => 'success',
        ],
        'AGOTADO' => [
            'label' => 'Agotado en Proveedores',
            'short' => 'Agotado',
            'bg'    => '#FF0000',
            'text'  => '#FFFFFF',
            'badge' => 'danger',
        ],
        'EN_TRANSITO' => [
            'label' => 'Stock en Tránsito - X Llegar a Lima',
            'short' => 'En Tránsito',
            'bg'    => '#FFC000',
            'text'  => '#000000',
            'badge' => 'warning',
        ],
        'DESHABILITADA' => [
            'label' => 'Deshabilitada en Proveedor',
            'short' => 'Deshabilitada',
            'bg'    => '#7030A0',
            'text'  => '#FFFFFF',
            'badge' => 'secondary',
        ],
        'PAUSADA' => [
            'label' => 'Publicación Pausada',
            'short' => 'Pausada',
            'bg'    => '#6C757D',
            'text'  => '#FFFFFF',
            'badge' => 'dark',
        ],
        'NORMAL' => [
            'label' => 'En Seguimiento (Color por Marca)',
            'short' => 'Seguimiento',
            'bg'    => '',
            'text'  => '#212529',
            'badge' => 'light',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gamas de Procesador
    |--------------------------------------------------------------------------
    */
    'gamas' => [
        'CELERON'                   => 'CELERON',
        'core i3 / CORE 3'          => 'core i3 / CORE 3',
        'core i5 / CORE 5 / ultra 5' => 'core i5 / CORE 5 / ultra 5',
        'core i7 / CORE 7 / ultra 7' => 'core i7 / CORE 7 / ultra 7',
        'core i9 / CORE 9 / ultra 9' => 'core i9 / CORE 9 / ultra 9',
        'RYZEN 5 / RYZEN AI 5'      => 'RYZEN 5 / RYZEN AI 5',
        'RYZEN 7 / RYZEN AI 7'      => 'RYZEN 7 / RYZEN AI 7',
        'RYZEN 9 / RYZEN AI 9'      => 'RYZEN 9 / RYZEN AI 9',
    ],
];
