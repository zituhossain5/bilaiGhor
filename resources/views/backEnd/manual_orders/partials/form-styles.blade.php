<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.mo-create .card { border: 1px solid #e7edf5; border-radius: 10px; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
.mo-create label { font-weight: 700; font-size: 12px; color: #344054; }
.mo-items th { font-size: 12px; color: #667085; white-space: nowrap; }
.mo-summary { background: #fff8ec; border: 1px solid #f3cf9e; border-radius: 10px; padding: 16px; position: sticky; top: 90px; }
.mo-summary-row { display: flex; justify-content: space-between; gap: 16px; padding: 7px 0; border-bottom: 1px dashed #e7c48e; }
.mo-summary-row.total { margin-top: 8px; padding: 12px; background: #3a1f0f; color: #fff; border-radius: 8px; border: none; font-weight: 800; }
.mo-stock-hint { display: block; margin-top: 5px; font-size: 11px; font-weight: 700; color: #198754; }
.mo-stock-hint.is-warning { color: #dc3545; }
.mo-payment-status { text-transform: capitalize; }

/* Customer search (Select2) and mobile-number suggestions */
.select2-container .select2-selection--single { height: calc(1.5em + .9rem + 2px); border-color: #ced4da; }
.select2-container .select2-selection--single .select2-selection__rendered { line-height: calc(1.5em + .9rem); }
.select2-container .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .9rem); }
.mo-cust-line { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.mo-cust-name { font-weight: 600; }
.mo-cust-meta { color: #667085; font-size: 12px; }
.mo-badge-manual { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 50rem; background: rgba(255, 188, 0, .18); color: #b98900; }
.mo-phone-wrap { position: relative; }
.mo-suggest { position: absolute; z-index: 1056; left: 0; right: 0; top: 100%; margin: 2px 0 0; padding: 4px 0; list-style: none; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; box-shadow: 0 8px 24px rgba(15,23,42,.12); max-height: 260px; overflow-y: auto; }
.mo-suggest li { padding: 7px 12px; cursor: pointer; font-size: 13px; }
.mo-suggest li.is-active, .mo-suggest li:hover { background: #f2f4f7; }
.mo-match-hint { margin-top: 6px; font-size: 12px; padding: 6px 10px; border-radius: 6px; background: #ecfdf3; color: #067647; }
.mo-match-hint button { border: 0; background: none; padding: 0; color: #1570ef; font-weight: 700; text-decoration: underline; }
</style>
