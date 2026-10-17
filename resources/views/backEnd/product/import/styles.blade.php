<style>
    .pi-card { border: none; border-radius: 12px; box-shadow: 0 0 20px rgba(18, 38, 63, 0.04); }
    .pi-card .card-body { padding: 22px; }
    .pi-step { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; margin-bottom: 10px; border-radius: 50%; background: #2a1505; color: #fdf9f4; font-weight: 700; }
    .pi-tips { padding-left: 18px; color: #6b5a4c; font-size: 13px; }
    .pi-tips li { margin-bottom: 4px; }
    .pi-modes { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
    .pi-mode { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border: 1px solid #e8dccb; border-radius: 10px; cursor: pointer; background: #fffaf3; }
    .pi-mode:has(input:checked) { border-color: #e8861a; box-shadow: 0 0 0 3px rgba(232, 134, 26, 0.15); background: #fff; }
    .pi-mode.is-disabled { opacity: .55; cursor: not-allowed; }
    .pi-mode input { margin-top: 4px; }
    .pi-mode small { display: block; color: #7a6a5e; margin-top: 2px; }

    .pi-stats { display: flex; flex-wrap: wrap; gap: 10px; }
    .pi-stat { min-width: 120px; padding: 10px 14px; border-radius: 10px; background: #fff8f0; border: 1px solid #f0e2cf; }
    .pi-stat strong { display: block; font-size: 20px; line-height: 1.2; color: #2a1505; }
    .pi-stat span { font-size: 12px; color: #7a6a5e; }
    .pi-stat.is-ok strong { color: #1e8449; }
    .pi-stat.is-warning strong { color: #b9770e; }
    .pi-stat.is-error strong { color: #c0392b; }

    .pi-progress { height: 10px; border-radius: 6px; background: #f1e6d8; overflow: hidden; }
    .pi-progress > div { height: 100%; width: 0; background: #e8861a; transition: width .3s ease; }

    .pi-table { font-size: 13px; }
    .pi-table thead th { background: #f9fbfd; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #8391a2; white-space: nowrap; }
    .pi-table td { vertical-align: top; }
    .pi-table tr.is-error td { background: #fdf3f2; }
    .pi-table tr.is-warning td { background: #fffaf0; }
    .pi-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .pi-badge.ok { background: #d9f2e3; color: #1e8449; }
    .pi-badge.warning { background: #fff1cc; color: #8a5a00; }
    .pi-badge.error { background: #f9d6d5; color: #a93226; }
    .pi-badge.create { background: #e8f1fd; color: #1f5fae; }
    .pi-badge.update { background: #efe7fb; color: #6b3fa0; }
    .pi-auto { display: inline-block; margin: 0 4px 4px 0; padding: 1px 7px; border-radius: 9px; border: 1px dashed #e8861a; color: #9a5a10; font-size: 11px; cursor: help; white-space: nowrap; }
    .pi-msgs { margin: 0; padding-left: 16px; }
    .pi-msgs li { margin-bottom: 2px; }
    .pi-msgs li.error { color: #a93226; }
    .pi-msgs li.warning { color: #8a5a00; }
    .pi-sku { color: #8391a2; font-size: 12px; }
</style>
