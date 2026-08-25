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
    font-size: 9.5px;
    line-height: 1.28;
}
.mi-top { width: 100%; margin: 0 0 5mm; border-collapse: collapse; table-layout: fixed; }
.mi-top td { padding: 0; border: 0; vertical-align: middle; }
.mi-logo { width: 43%; text-align: left; }
.mi-logo img { display: block; width: 24mm; height: 16mm; max-width: 100%; object-fit: contain; object-position: left center; }
.mi-title { width: 57%; padding-left: 3mm !important; text-align: right; overflow: visible; }
.mi-title h1 { margin: 0 0 3px; font-size: 18px; line-height: 1.05; font-weight: 500; color: #1f1f1f; }
.mi-title p { margin: 0 0 2px; font-size: 8.2px; line-height: 1.25; color: #111; white-space: nowrap; }
.mi-customer { margin-bottom: 4mm; padding: 3mm; background: #eeeeee; border-radius: 2mm; }
.mi-box-title { margin: 0 0 3mm; font-size: 10px; font-weight: 700; }
.mi-details { width: 100%; border-collapse: collapse; table-layout: fixed; }
.mi-details td { padding: 0 0 1.8mm; border: 0; vertical-align: top; font-size: 8.5px; }
.mi-details tr:last-child td { padding-bottom: 0; }
.mi-details .mi-label { width: 15mm; padding-right: 2mm; color: #222; }
.mi-value { color: #111; overflow-wrap: anywhere; word-wrap: break-word; }
.mi-table { width: 100%; margin-bottom: 4mm; border-collapse: collapse; table-layout: fixed; }
.mi-table th { padding: 0 0 2mm; border-bottom: 1px solid #bfbfbf; font-size: 8.5px; font-weight: 400; color: #555; text-align: left; }
.mi-table td { padding: 2.2mm 0; border-bottom: 1px dotted #d2d2d2; vertical-align: top; font-size: 8.2px; page-break-inside: avoid; overflow-wrap: break-word; word-wrap: break-word; }
.mi-table .mi-product-col { width: 48%; padding-right: 2mm; }
.mi-table .mi-qty-col { width: 10%; }
.mi-table .mi-price-col { width: 18%; }
.mi-table .mi-amount-col { width: 24%; }
.mi-table .mi-num { text-align: right; white-space: nowrap; }
.mi-product-name { font-weight: 600; color: #222; overflow-wrap: anywhere; word-wrap: break-word; }
.mi-product-variant { margin-top: 1mm; color: #555; font-size: 7.8px; }
.mi-summary { width: 70%; margin: 0 0 3mm auto; border-collapse: collapse; page-break-inside: avoid; }
.mi-summary td { padding: 1.4mm 0; border: 0; font-size: 8.5px; }
.mi-summary .mi-summary-value { text-align: right; white-space: nowrap; }
.mi-summary .mi-total-label,
.mi-summary .mi-total-value { padding: 2mm 3mm; background: #eeeeee; font-weight: 700; }
.mi-summary .mi-total-label { border-radius: 3px 0 0 3px; }
.mi-summary .mi-total-value { border-radius: 0 3px 3px 0; text-align: right; white-space: nowrap; }
.mi-payment { margin: 0 0 5mm; font-size: 7.8px; color: #222; }
.mi-thanks { margin: 0 0 4mm; text-align: center; font-size: 8.5px; }
.mi-footer { width: 100%; border-collapse: collapse; table-layout: fixed; }
.mi-footer td { padding: 0; border: 0; vertical-align: bottom; }
.mi-social { width: 63%; padding-right: 3mm !important; font-size: 8px; color: #111; }
.mi-social-line { display: inline-block; width: 49%; margin-top: 0; white-space: normal; vertical-align: middle; }
.mi-social-line:first-child { margin-top: 0; }
.mi-social i { display: inline-block; width: 4mm; height: 4mm; margin-right: 1.5mm; border-radius: 50%; background: #111; color: #fff; text-align: center; line-height: 4mm; font-size: 2.4mm; }
.mi-qr { width: 37%; text-align: right; font-size: 7px; color: #555; }
.mi-qr img { display: block; width: 19mm; height: 19mm; max-width: 100%; margin-left: auto; padding: .5mm; border: 1px solid #dedede; background: #fff; object-fit: contain; }
.mi-qr-caption { margin-top: 1mm; white-space: nowrap; }

@media print {
    html, body { width: auto !important; max-width: 100% !important; min-height: 0 !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
    .mi-doc { width: 80mm; max-width: 80mm; margin: 0 auto; padding: 6mm 5mm; border: 0; border-radius: 0; box-shadow: none; }
}

@media screen and (max-width: 90mm) {
    .mi-doc { width: 100%; max-width: 100%; }
}
</style>
