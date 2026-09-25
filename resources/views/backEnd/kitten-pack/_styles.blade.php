<link href="{{ asset('public/backEnd') }}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,0.03); border-radius: 12px; background: #fff; margin-bottom: 24px; }
    .card-header { background: #fff; border-bottom: 1px solid #f1f5f7; padding: 20px 25px; display: flex; align-items: center; gap: 10px; }
    .card-title { font-size: 16px; font-weight: 700; color: #2d3436; margin: 0; }
    .header-icon { width: 35px; height: 35px; background: rgba(114,124,245,0.1); color: #727cf5; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .form-label { font-weight: 600; font-size: 13px; color: #636e72; margin-bottom: 8px; }
    .form-control, .form-select { background-color: #fbfcff; border: 1px solid #eef2f7; padding: 10px 15px; border-radius: 8px; font-size: 14px; color: #2d3436; transition: all 0.3s; }
    .form-control:focus, .form-select:focus { background-color: #fff; border-color: #727cf5; box-shadow: 0 0 0 4px rgba(114,124,245,0.1); }
    .image-upload-box { border: 2px dashed #eef2f7; border-radius: 10px; padding: 24px 20px; text-align: center; cursor: pointer; background: #f9fbfd; min-height: 180px; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: 0.3s; }
    .image-upload-box:hover { border-color: #727cf5; background: #fff; }
    .upload-placeholder i { font-size: 30px; color: #98a6ad; margin-bottom: 8px; }
    .upload-placeholder p { font-size: 13px; color: #6c757d; font-weight: 500; margin: 0; }
    .preview-img { max-width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px; display: block; background: #fff; }
    .pack-item-check { display: flex; align-items: center; gap: 6px; margin: 0; font-size: 13px; color: #636e72; white-space: nowrap; }
    .btn-remove-row { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border: 1px solid #eef2f7; border-radius: 8px; background: #f9fbfd; color: #8391a2; transition: 0.2s; }
    .btn-remove-row:hover { background: rgba(250,92,124,0.1); color: #fa5c7c; border-color: transparent; }
    .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #eef2f7; transition: .4s; border-radius: 34px; border: 1px solid #dee2e6; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    input:checked + .slider { background-color: #0acf97; border-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(22px); }
    .btn-submit { background: linear-gradient(45deg,#0acf97,#06b6d4); border: none; color: white; padding: 12px; font-weight: 600; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(10,207,151,0.3); transition: 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(10,207,151,0.4); color: #fff; }
    .item-picker { display: grid; grid-template-columns: 1fr 90px auto; gap: 10px; align-items: center; }
    .item-table th { font-size: 12px; font-weight: 600; color: #8391a2; text-transform: uppercase; border-bottom: 1px solid #f1f5f7; }
    .item-table td { vertical-align: middle; font-size: 14px; color: #2d3436; }
    .pack-item-row { transition: background 0.3s; }
    .pack-item-row.is-highlighted { background: rgba(255,188,0,0.15); }
    .pack-item-row.needs-product { background: rgba(250,92,124,0.05); }
    .pack-item-row:has(.item-included:not(:checked)) .item-name { text-decoration: line-through; color: #98a6ad; }
    .needs-product-note { font-size: 12px; font-weight: 600; color: #fa5c7c; margin-bottom: 6px; }
    .stock-badge { display: inline-block; min-width: 38px; padding: 3px 10px; border-radius: 20px; background: #f1f5f7; font-size: 12px; font-weight: 600; text-align: center; }
    .item-summary { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding: 12px 15px; border-radius: 8px; background: #f9fbfd; font-size: 13px; color: #636e72; }
    @media (max-width: 575px) {
        .item-picker { grid-template-columns: 1fr 70px; }
        .item-picker .btn { grid-column: 1 / -1; }
    }
</style>
