<style>
@font-face {
    font-family: 'Bilai Receipt';
    src: url('{{ $receiptFontUrl }}') format('truetype');
    font-weight: 400;
    font-style: normal;
}
@font-face {
    font-family: 'Bilai Receipt';
    src: url('{{ $receiptBoldFontUrl }}') format('truetype');
    font-weight: 700;
    font-style: normal;
}
@page { size: A4 portrait; margin: 10mm; }

/*
 * Compact receipt. Item rows are one line of a fixed height, so App\Services\ReceiptLayout
 * can tell how many fit on an A4 page. Keep its height constants in step with the sizes here.
 */
.mi-standalone { width: auto; max-width: 100%; margin: 0; padding: 0; background: #fff; }
.mi-doc,
.mi-doc * { box-sizing: border-box; }
.mi-doc {
    width: 80mm;
    max-width: 80mm;
    margin: 0 auto;
    padding: 6mm 5mm;
    color: #222;
    background: #fff;
    font-family: 'Bilai Receipt', DejaVu Sans, Arial, Helvetica, sans-serif;
    font-size: 8px;
    line-height: 1.3;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.mi-doc-break { page-break-before: always; break-before: page; }

/* Header: logo left, "Receipt" + "BG-… · date" right */
.mi-top { width: 100%; margin: 0 0 2.5mm; border-collapse: collapse; table-layout: fixed; }
.mi-top td { padding: 0; border: 0; vertical-align: middle; }
.mi-logo { width: 40%; text-align: left; }
.mi-logo img { display: block; width: 18mm; height: 11mm; max-width: 100%; object-fit: contain; object-position: left center; }
.mi-title { width: 60%; text-align: right; }
.mi-title h1 { margin: 0 0 1mm; font-size: 13px; line-height: 1.05; font-weight: 400; color: #1f1f1f; }
.mi-title p { margin: 0; font-size: 8px; line-height: 1.3; color: #111; white-space: nowrap; }
.mi-page-no { color: #777 !important; }

/* Customer: "Name · phone", then the address (at most 3 lines) */
.mi-customer { margin: 0 0 2.5mm; padding: 0 0 2mm; border-bottom: 1px solid #e3e3e3; }
.mi-customer-line { font-size: 8.5px; line-height: 3.6mm; color: #111; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.mi-address { max-height: 9.9mm; overflow: hidden; font-size: 8px; line-height: 3.3mm; color: #333; overflow-wrap: anywhere; word-wrap: break-word; }

/* Following pages repeat the order ID and page number */
.mi-continued { margin: 0 0 2mm; height: 4mm; line-height: 4mm; font-size: 8px; color: #555; }

/* Items: one line per row */
.mi-table { width: 100%; margin: 0 0 2mm; border-collapse: collapse; table-layout: fixed; }
.mi-table th { height: 4.2mm; padding: 0 0 .6mm; border-bottom: 1px solid #bdbdbd; font-size: 7.5px; font-weight: 400; color: #666; text-align: left; vertical-align: bottom; }
.mi-table td { height: 4.2mm; padding: 0; line-height: 4mm; border-bottom: 1px solid #ececec; font-size: 8px; vertical-align: middle; white-space: nowrap; overflow: hidden; page-break-inside: avoid; break-inside: avoid; }
.mi-table tr { page-break-inside: avoid; break-inside: avoid; }
/* Widths sit on the header cells: the PDF renderer ignores <col> widths. */
.mi-table .mi-item-col { width: 62%; }
.mi-table .mi-qty-col { width: 8%; }
.mi-table .mi-price-col { width: 14%; }
.mi-table .mi-amount-col { width: 16%; }
.mi-table .mi-num { text-align: right; }
.mi-item { padding-right: 1.5mm !important; text-overflow: ellipsis; }
.mi-item-name { font-weight: 700; color: #222; white-space: nowrap; }
.mi-item-variant { color: #888; font-size: 7px; line-height: 1; white-space: nowrap; }

/* Long orders: smaller rows */
.mi-dense .mi-table td { height: 3.1mm; line-height: 2.9mm; font-size: 7.2px; }
.mi-dense .mi-item-variant { font-size: 6.4px; }

/* Comma-list mode */
.mi-comma-list { margin: 0 0 3mm; font-size: 8px; line-height: 1.45; color: #222; overflow-wrap: anywhere; }

/* Totals: only rows with a value; the payment line sits under TOTAL */
.mi-summary { width: 68%; margin: 0 0 3mm auto; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
.mi-summary-label-col { width: 58%; }
.mi-summary-value-col { width: 42%; }
.mi-summary td { height: 4.2mm; padding: 0 2mm; border: 0; font-size: 8px; line-height: 4.2mm; }
.mi-summary .mi-summary-label { text-align: left; }
.mi-summary .mi-summary-value { text-align: right; white-space: nowrap; }
.mi-summary .mi-total-row td { height: 5mm; line-height: 5mm; background: #eeeeee !important; font-size: 8.5px; font-weight: 700; }
.mi-summary .mi-total-row .mi-summary-label { border-radius: 3px 0 0 3px; }
.mi-summary .mi-total-row .mi-summary-value { border-radius: 0 3px 3px 0; }
.mi-summary .mi-payment td { padding-top: .6mm; font-size: 7.5px; color: #444; text-align: right; white-space: nowrap; }

/* Footer: thank-you + Facebook + WhatsApp on the left, QR on the right, one row */
.mi-footer { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
.mi-footer td { padding: 0; border: 0; vertical-align: middle; }
.mi-footer-text { width: 72%; padding-right: 2mm !important; font-size: 7.6px; color: #111; }
.mi-thanks { margin: 0 0 1.4mm; font-size: 8.5px; font-weight: 700; }
.mi-social-line { display: table; margin-top: .9mm; white-space: nowrap; }
.mi-social-line > span { display: table-cell; vertical-align: middle; }
.mi-social-icon { width: 5mm; }
.mi-social-icon img { display: block; width: 3.6mm; height: 3.6mm; padding: .65mm; border-radius: 50%; background: #111; object-fit: contain; }
.mi-qr { width: 28%; text-align: right; font-size: 6.5px; color: #666; }
.mi-qr img { display: block; width: 16mm; height: 16mm; max-width: 100%; margin-left: auto; padding: .4mm; border: 1px solid #dedede; background: #fff; object-fit: contain; }
.mi-qr-caption { margin-top: .6mm; white-space: nowrap; }

@media print {
    html, body { width: auto !important; max-width: 100% !important; min-height: 0 !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
    .mi-doc { width: 80mm; max-width: 80mm; margin: 0 auto; padding: 6mm 5mm; border: 0; border-radius: 0; box-shadow: none; }
    .mi-summary .mi-total-row td { background: #eeeeee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .mi-print-page .mi-doc { margin: 0; }
}

@media screen and (max-width: 90mm) {
    .mi-doc { width: 100%; max-width: 100%; }
}
</style>
