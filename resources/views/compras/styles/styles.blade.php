<style>
    .deltron-kpi-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .deltron-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    }
    .badge-soft-success {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    .badge-soft-primary {
        background-color: #cfe2ff;
        color: #084298;
    }
    .badge-soft-info {
        background-color: #cff4fc;
        color: #055160;
    }
    .badge-soft-warning {
        background-color: #fff3cd;
        color: #664d03;
    }
    .warehouse-badge-positive {
        background-color: #198754;
        color: #ffffff;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .warehouse-badge-zero {
        background-color: #f8f9fa;
        color: #adb5bd;
        border: 1px solid #dee2e6;
        font-weight: 500;
        padding: 4px 8px;
        border-radius: 20px;
    }
    .btn-copy-mini {
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.2s;
    }
    .btn-copy-mini:hover {
        opacity: 1;
        color: #0d6efd;
    }
    .table-deltron tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-deltron tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.03);
    }
    .btn-promo-detail {
        transition: all 0.2s ease;
    }
    .btn-promo-detail:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(255, 193, 7, 0.35);
    }
    .shadow-xs {
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .deltron-specs-container table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 0.5rem;
        background-color: #ffffff;
    }
    .deltron-specs-container table td,
    .deltron-specs-container table th {
        padding: 6px 10px !important;
        border: 1px solid #dee2e6 !important;
        font-size: 0.85rem !important;
        color: #212529 !important;
        vertical-align: top;
    }
    .deltron-specs-container table tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    .deltron-specs-container font {
        font-family: inherit !important;
        font-size: inherit !important;
        color: inherit !important;
    }
    .deltron-img-card {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #dee2e6;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background-color: #fff;
    }
    .deltron-img-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.1);
    }
    .deltron-img-card img {
        width: 100%;
        height: 180px;
        object-fit: contain;
        padding: 8px;
    }
</style>
