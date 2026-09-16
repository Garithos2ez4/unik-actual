<style>
    .card-sistema {
        background-color: #ffffff;
        border: 1px solid #e0e4e8;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
    }

    th.bg-sistema-uno {
        background-color: #043e69 !important;
        color: #ffffff !important;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-bottom: 0 !important;
        padding: 15px 12px !important;
    }

    .table-custom td {
        vertical-align: middle;
        color: #333;
        border-bottom: 1px solid #f1f3f5;
        padding: 12px;
    }

    .table-custom tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge-wsp {
        background: linear-gradient(135deg, #25d366, #128c7e);
        color: white;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
    }

    #manualModalMap {
        height: 250px;
        width: 100%;
        border-radius: 8px;
    }

    .producto-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 8px;
    }

    .producto-search-results {
        position: absolute;
        z-index: 1055;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        max-height: 200px;
        overflow-y: auto;
        width: calc(100% - 24px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .producto-search-results .list-group-item {
        cursor: pointer;
        border: none;
        padding: 8px 12px;
    }

    .producto-search-results .list-group-item:hover {
        background-color: #e9ecef;
    }
    
    /* Fix para el dropdown de Google Maps Autocomplete dentro de modales Bootstrap */
    .pac-container {
        z-index: 1060 !important;
    }
</style>
