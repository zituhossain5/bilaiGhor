<style>
    .fc-page {
        --fc-brown: var(--bilai-admin-dark, #2a1505);
        --fc-orange: var(--bilai-admin-orange, #e8861a);
        --fc-cream: var(--bilai-admin-surface, #fdf9f4);
        --fc-cream-2: var(--bilai-admin-muted-bg, #f0e6d8);
        --fc-border: var(--bilai-admin-border, #dccab2);
        --fc-muted: var(--bilai-admin-muted, #7a6a5e);
        --fc-good: #16884d;
        --fc-mid: #c9780a;
        --fc-bad: #b42318;
        --fc-none: #9b8f84;
        padding-bottom: 24px;
    }
    .fc-card { background: #fff; border: 1px solid var(--fc-border); border-radius: 14px; box-shadow: 0 2px 10px rgba(42, 21, 5, .05); margin-bottom: 18px; }
    .fc-card-body { padding: 18px 20px; }
    .fc-card-title { font-size: 15px; font-weight: 700; color: var(--fc-brown); margin: 0 0 12px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* Search */
    .fc-search { background: linear-gradient(135deg, #3a1f0f 0%, #2a1505 100%); color: #f0e6d8; border: 0; }
    .fc-search h5 { color: #fff; font-weight: 700; margin: 0 0 12px; font-size: 17px; }
    .fc-search-form { display: flex; gap: 8px; max-width: 520px; }
    .fc-search-form input { flex: 1; min-width: 0; font-size: 17px; letter-spacing: .5px; padding: 10px 14px; border-radius: 10px; border: 2px solid transparent; background: #fffdf8; }
    .fc-search-form input:focus { border-color: var(--fc-orange); outline: 0; box-shadow: 0 0 0 3px rgba(232, 134, 26, .3); }
    .fc-btn { background: var(--fc-orange); border: 0; color: #fff; font-weight: 700; border-radius: 10px; padding: 10px 18px; white-space: nowrap; }
    .fc-btn:hover, .fc-btn:focus { background: var(--bilai-admin-orange-dark, #c96f00); color: #fff; }
    .fc-btn:disabled { opacity: .7; }
    .fc-hint { font-size: 12.5px; color: #d9c6ad; margin-top: 8px; }
    .fc-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 12px; font-size: 12.5px; }
    .fc-chips form { margin: 0; }
    .fc-chip { border: 1px solid rgba(240, 230, 216, .35); background: rgba(255, 255, 255, .08); color: #fff; border-radius: 999px; padding: 3px 12px; font-size: 13px; letter-spacing: .3px; }
    .fc-chip:hover { background: rgba(232, 134, 26, .35); border-color: var(--fc-orange); }

    .fc-alert { border-radius: 10px; padding: 10px 14px; font-size: 13.5px; margin-bottom: 14px; display: flex; gap: 10px; align-items: flex-start; }
    .fc-search .fc-alert { margin: 12px 0 0; }
    .fc-alert-error { background: #fdecea; color: #8a1c12; border: 1px solid #f5c2bc; }
    .fc-alert-warn { background: #fff6e0; color: #7a4b00; border: 1px solid #f1d38f; }

    /* Source bar */
    .fc-source { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; font-size: 13px; color: var(--fc-muted); margin-bottom: 12px; }
    .fc-source strong { color: var(--fc-brown); }
    .fc-source form { margin: 0; }
    .fc-btn-outline { border: 1px solid var(--fc-orange); color: var(--bilai-admin-orange-dark, #c96f00); background: #fff; border-radius: 999px; padding: 4px 14px; font-weight: 600; font-size: 13px; }
    .fc-btn-outline:hover { background: var(--fc-orange); color: #fff; }

    /* Summary */
    .fc-summary-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 4px; }
    .fc-phone { font-size: 20px; font-weight: 800; color: var(--fc-brown); letter-spacing: .5px; }
    .fc-badge { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 12px; font-size: 13px; font-weight: 700; }
    .fc-badge::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .fc-risk-new { background: #efebe7; color: #5f554c; }
    .fc-risk-safe { background: #e3f4ea; color: var(--fc-good); }
    .fc-risk-medium { background: #fff1d6; color: #9a5b00; }
    .fc-risk-high { background: #fde4e1; color: var(--fc-bad); }

    .fc-gauge { max-width: 240px; margin: 6px auto 0; text-align: center; }
    .fc-gauge-dial { position: relative; }
    .fc-gauge svg { width: 100%; display: block; overflow: visible; }
    .fc-gauge-track, .fc-gauge-fill { fill: none; stroke-width: 12; stroke-linecap: round; }
    .fc-gauge-track { stroke: var(--fc-cream-2); }
    .fc-tone-good .fc-gauge-fill { stroke: var(--fc-good); }
    .fc-tone-mid .fc-gauge-fill { stroke: var(--fc-mid); }
    .fc-tone-bad .fc-gauge-fill { stroke: var(--fc-bad); }
    .fc-gauge-value { position: absolute; left: 0; right: 0; bottom: 10%; font-size: 30px; font-weight: 800; color: var(--fc-brown); line-height: 1; }
    .fc-tone-none .fc-gauge-value { font-size: 20px; color: var(--fc-none); }
    .fc-gauge-label { font-size: 12.5px; color: var(--fc-muted); margin-top: 6px; }
    .fc-method { text-align: center; font-size: 12px; color: var(--fc-muted); margin: 6px 0 14px; }

    .fc-tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .fc-tile { background: var(--fc-cream); border: 1px solid var(--fc-cream-2); border-radius: 12px; padding: 10px 12px; color: inherit; text-decoration: none; display: block; min-width: 0; }
    a.fc-tile:hover { border-color: var(--fc-orange); }
    .fc-tile-label { font-size: 12px; color: var(--fc-muted); }
    .fc-tile-value { font-size: 20px; font-weight: 800; color: var(--fc-brown); line-height: 1.25; }
    .fc-tile-value small { font-size: 13px; font-weight: 600; color: var(--fc-muted); }
    .fc-tile-note { font-size: 11px; color: var(--fc-muted); }
    .fc-tile-good .fc-tile-value { color: var(--fc-good); }
    .fc-tile-bad { background: #fdecea; border-color: #f5c2bc; }
    .fc-tile-bad .fc-tile-value, .fc-tile-bad .fc-tile-label { color: var(--fc-bad); }

    .fc-risk-box { margin-top: 14px; border-radius: 12px; padding: 12px 14px; font-size: 13.5px; }
    .fc-risk-box strong { display: block; font-size: 15px; margin-bottom: 2px; }
    .fc-risk-box.fc-risk-new { border: 1px solid #ddd4cb; }
    .fc-risk-box.fc-risk-safe { border: 1px solid #b7e0c6; }
    .fc-risk-box.fc-risk-medium { border: 1px solid #f1d38f; }
    .fc-risk-box.fc-risk-high { border: 1px solid #f5c2bc; }
    .fc-verdict { font-size: 12.5px; color: var(--fc-muted); margin-top: 10px; }
    .fc-verdict ul { margin: 4px 0 0; padding-left: 18px; }

    /* Courier table */
    .fc-table-wrap { overflow-x: auto; }
    .fc-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; }
    .fc-table th { background: var(--fc-brown); color: #f6ead8; font-weight: 600; font-size: 12.5px; padding: 9px 8px; text-align: center; }
    .fc-table th:first-child { text-align: left; border-radius: 10px 0 0 0; }
    .fc-table th:last-child { border-radius: 0 10px 0 0; }
    .fc-table td { padding: 10px 8px; border-bottom: 1px solid var(--fc-cream-2); text-align: center; vertical-align: middle; }
    .fc-table td:first-child { text-align: left; }
    .fc-courier { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--fc-brown); }
    .fc-courier img { width: 46px; height: 30px; object-fit: contain; border-radius: 4px; background: #fff; }
    .fc-num { font-weight: 700; }
    .fc-num-good { color: var(--fc-good); }
    .fc-num-bad { color: var(--fc-bad); }
    .fc-old-counts { color: var(--fc-muted); font-style: italic; }
    .fc-range { display: inline-block; background: var(--fc-cream-2); color: var(--fc-brown); border-radius: 999px; padding: 1px 9px; font-weight: 700; font-size: 12.5px; }
    .fc-rate { min-width: 96px; }
    .fc-rate-text { font-weight: 800; font-size: 15px; }
    .fc-bar { height: 6px; background: var(--fc-cream-2); border-radius: 999px; overflow: hidden; margin-top: 4px; }
    .fc-bar span { display: block; height: 100%; border-radius: 999px; }
    .fc-tone-good .fc-rate-text { color: var(--fc-good); } .fc-tone-good .fc-bar span { background: var(--fc-good); }
    .fc-tone-mid .fc-rate-text { color: var(--fc-mid); } .fc-tone-mid .fc-bar span { background: var(--fc-mid); }
    .fc-tone-bad .fc-rate-text { color: var(--fc-bad); } .fc-tone-bad .fc-bar span { background: var(--fc-bad); }
    .fc-tone-none .fc-rate-text { color: var(--fc-none); font-weight: 600; font-size: 13px; }
    .fc-src { font-size: 12px; color: var(--fc-muted); white-space: nowrap; }
    .fc-snap { display: inline-block; margin-top: 3px; background: #fff3dc; color: #7a4b00; border: 1px solid #f1d38f; border-radius: 8px; padding: 1px 8px; font-size: 11.5px; line-height: 1.35; max-width: 120px; cursor: help; }
    .fc-total-row td { background: var(--fc-cream); font-weight: 800; color: var(--fc-brown); border-bottom: 0; }
    .fc-nodata { margin-top: 10px; background: #f6f3f0; color: #6f655c; border-radius: 10px; padding: 8px 12px; font-size: 13px; }
    .fc-tip { border: 0; background: none; color: var(--bilai-admin-orange-dark, #c96f00); font-size: 14px; line-height: 1; padding: 0 2px; cursor: help; vertical-align: middle; }

    /* How it's calculated */
    .fc-how summary { cursor: pointer; font-weight: 700; color: var(--fc-brown); list-style: none; display: flex; align-items: center; gap: 8px; }
    .fc-how summary::-webkit-details-marker { display: none; }
    .fc-how summary::after { content: '▾'; margin-left: auto; transition: transform .2s; }
    .fc-how[open] summary::after { transform: rotate(180deg); }
    .fc-how ol { margin: 12px 0 0; padding-left: 20px; font-size: 13.5px; color: #4f4338; }
    .fc-how li { margin-bottom: 6px; }

    /* Store */
    .fc-store-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; text-align: center; }
    .fc-store-stats div { background: var(--fc-cream); border-radius: 10px; padding: 8px 4px; }
    .fc-store-stats b { display: block; font-size: 18px; color: var(--fc-brown); }
    .fc-store-stats span { font-size: 11.5px; color: var(--fc-muted); }
    .fc-store-list { list-style: none; margin: 10px 0 0; padding: 0; font-size: 13px; }
    .fc-store-list li { display: flex; justify-content: space-between; gap: 8px; padding: 5px 0; border-bottom: 1px dashed var(--fc-cream-2); }

    /* Skeleton */
    .fc-skeleton { display: none; }
    .fc-loading .fc-skeleton { display: block; }
    .fc-loading #fc-results { display: none; }
    .fc-sk { background: linear-gradient(90deg, #f0e6d8 25%, #f9f3ea 50%, #f0e6d8 75%); background-size: 200% 100%; animation: fc-shimmer 1.2s infinite; border-radius: 10px; }
    @keyframes fc-shimmer { to { background-position: -200% 0; } }

    /* Settings */
    .fc-settings summary { cursor: pointer; font-weight: 700; color: var(--fc-brown); }
    .fc-settings label { font-weight: 600; color: var(--fc-brown); }
    .fc-settings .form-text { font-size: 12px; }

    @media (max-width: 575.98px) {
        .fc-card-body { padding: 14px; }
        .fc-search-form input { font-size: 16px; }
        .fc-phone { font-size: 18px; }
        .fc-store-stats { grid-template-columns: repeat(2, 1fr); }
        /* Courier rows become small cards */
        .fc-table-wrap { overflow: visible; }
        .fc-snap { max-width: none; }
        .fc-table thead { display: none; }
        .fc-table, .fc-table tbody, .fc-table tr, .fc-table td { display: block; width: 100%; }
        .fc-table tr { border: 1px solid var(--fc-cream-2); border-radius: 12px; margin-bottom: 10px; padding: 6px 4px; }
        .fc-table td { border: 0; display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 5px 10px; text-align: right; }
        .fc-table td::before { content: attr(data-label); font-size: 12px; color: var(--fc-muted); font-weight: 600; text-align: left; }
        .fc-table td:first-child::before { content: none; }
        .fc-table .fc-total-row { background: var(--fc-cream); }
        .fc-rate { min-width: 0; width: 100%; }
        .fc-rate > div { min-width: 140px; }
    }
</style>
